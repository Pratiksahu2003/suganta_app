<?php

namespace App\Services;

use App\Mail\ModelUpdateSecurityAlert;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ModelActivityNotificationService
{
    private const EXCLUDED_MODELS = [
        Notification::class,
    ];

    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    public function onCreated(Model $model): void
    {
        // Basic model-level guard (ignored_models, globally disabled, etc.).
        if (! $this->shouldNotifyFor($model)) {
            return;
        }

        $actor = Auth::user();
        $actorName = $actor?->name ?? 'System';
        $modelLabel = class_basename($model);
        $modelKey = $model->getKey();

        if ($this->isInCooldown($model, 'created')) {
            return;
        }

        $isImportantEvent = $this->isImportantModelForEvent($model, 'created');

        // In-app / push notification: keep stricter gating so only
        // configured important models reach the notification feed.
        if ($isImportantEvent) {
            $this->notifyUsersBasedOnMode(
                title: "{$modelLabel} created",
                message: "{$actorName} created {$modelLabel}" . ($modelKey !== null ? " #{$modelKey}" : '.'),
                data: [
                    'event' => 'created',
                    'model' => get_class($model),
                    'model_label' => $modelLabel,
                    'model_id' => $modelKey,
                    'actor_user_id' => $actor?->id,
                ]
            );
        }

        // Security email on creation. Fires when the model is explicitly
        // force-listed, or when `email_on_all_creations` is on, or for any
        // important event.
        if ($this->shouldEmailOnCreation($model, $isImportantEvent)) {
            $providedFieldNames = array_values(array_filter(
                array_keys($model->getAttributes()),
                fn (string $field): bool => ! $this->isIgnoredField($model, $field)
            ));

            $createDiff = $this->buildCreateDiff($model, $providedFieldNames);

            // Skip the email entirely when the runtime filter left us with
            // nothing displayable (e.g. model had only id/json columns).
            if ($createDiff !== []) {
                $this->dispatchSecurityAlertEmail(
                    model: $model,
                    modelLabel: $modelLabel,
                    modelKey: $modelKey,
                    changedFields: $createDiff,
                    actor: $actor,
                    actorName: $actorName,
                    event: 'created',
                );
            }
        }
    }

    public function onUpdated(Model $model): void
    {
        // Basic model-level guard (ignored_models, globally disabled, etc.).
        // This already rejects system/token/session models like UserSession,
        // Otp, PersonalAccessToken, Chatbot webhook events, etc.
        if (! $this->shouldNotifyFor($model)) {
            return;
        }

        $changed = Arr::except($model->getChanges(), ['updated_at']);
        if ($changed === []) {
            return;
        }

        // Drop token / session / password / bookkeeping fields. If nothing
        // meaningful is left, the whole update is treated as system noise.
        $changedFieldNames = array_values(array_filter(
            array_keys($changed),
            fn (string $field): bool => ! $this->isIgnoredField($model, $field)
        ));
        if ($changedFieldNames === []) {
            return;
        }

        // Build an old -> new diff for each meaningful changed field so the
        // email can show exactly what was modified.
        $changeDiff = $this->buildChangeDiff($model, $changedFieldNames);

        $actor = Auth::user();
        $actorName = $actor?->name ?? 'System';
        $modelLabel = class_basename($model);
        $modelKey = $model->getKey();

        // Cooldown guard still applies (per-record, per-event) so a rapid
        // burst of writes won't spam the mailbox.
        if ($this->isInCooldown($model, 'updated')) {
            return;
        }

        // In-app / push notification: keep stricter gating so only important
        // models + important field changes reach the notification feed.
        $isImportantEvent = $this->isImportantModelForEvent($model, 'updated')
            && $this->hasImportantUpdateFieldChange($model, $changedFieldNames);

        if ($isImportantEvent) {
            $this->notifyUsersBasedOnMode(
                title: "{$modelLabel} updated",
                message: "{$actorName} updated {$modelLabel}" . ($modelKey !== null ? " #{$modelKey}" : '.'),
                data: [
                    'event' => 'updated',
                    'model' => get_class($model),
                    'model_label' => $modelLabel,
                    'model_id' => $modelKey,
                    'actor_user_id' => $actor?->id,
                    'changed_fields' => $changedFieldNames,
                ]
            );
        }

        // Security email: fires on EVERY non-system update (token/session/log
        // models & fields were already filtered out above). When
        // `email_on_all_updates` is disabled, fall back to the stricter
        // "important event" / force-list behaviour.
        $emailOnAllUpdates = (bool) config('push.model_activity.email_on_all_updates', true);
        if (($emailOnAllUpdates || $isImportantEvent || $this->isEmailForcedModel($model)) && $changeDiff !== []) {
            $this->dispatchSecurityAlertEmail(
                model: $model,
                modelLabel: $modelLabel,
                modelKey: $modelKey,
                changedFields: $changeDiff,
                actor: $actor,
                actorName: $actorName,
                event: 'updated',
            );
        }
    }

    private function shouldNotifyFor(Model $model): bool
    {
        if (! config('push.model_activity.enabled', true)) {
            return false;
        }

        $modelClass = get_class($model);
        if (in_array($modelClass, self::EXCLUDED_MODELS, true)) {
            return false;
        }

        $ignoredModels = config('push.model_activity.ignored_models', []);
        if (is_array($ignoredModels) && in_array($modelClass, $ignoredModels, true)) {
            return false;
        }

        return true;
    }

    private function isIgnoredField(Model $model, string $field): bool
    {
        // Always hide identifier / foreign-key columns (primary keys, `*_id`,
        // `*_uuid`) from create/update emails — they're noisy & leak internals.
        if ($this->isIdentifierField($field)) {
            return true;
        }

        // Hide any column whose NAME matches sensitive-data patterns
        // (passwords, tokens, secrets, OTPs, 2FA, card / bank / national-id,
        // etc.). This is a hard-coded defense-in-depth layer so no amount
        // of config drift can leak secrets into the email template.
        if ($this->isSensitiveField($field)) {
            return true;
        }

        // Hide any column cast as JSON / array / object / collection on the
        // model so we never render raw JSON blobs in the email template.
        if ($this->isJsonCastField($model, $field)) {
            return true;
        }

        $globalIgnoredFields = config('push.model_activity.ignored_fields', []);
        $modelIgnoredFieldsMap = config('push.model_activity.model_ignored_fields', []);

        if (is_array($globalIgnoredFields) && in_array($field, $globalIgnoredFields, true)) {
            return true;
        }

        $modelClass = get_class($model);
        $modelIgnoredFields = is_array($modelIgnoredFieldsMap[$modelClass] ?? null)
            ? $modelIgnoredFieldsMap[$modelClass]
            : [];

        return in_array($field, $modelIgnoredFields, true);
    }

    /**
     * Hard-coded list of sensitive field-name patterns. A field matches if
     * its lowercased name contains any of these tokens OR matches one of
     * the stricter word-boundary patterns.
     *
     * Covers: authentication, authorization, personal identifiers, payment
     * details, and cryptographic material. Must stay broad on purpose.
     */
    private function isSensitiveField(string $field): bool
    {
        $lower = strtolower($field);

        // Simple "contains" probes — fast path for the vast majority of cases.
        $containsPatterns = [
            // Auth & credentials
            'password', 'passwd', 'pwd', 'secret', 'token', 'auth', 'credential',
            'api_key', 'apikey', 'access_key', 'accesskey', 'private_key', 'privatekey',
            'public_key', 'publickey', 'encryption_key', 'signing_key', 'signature',
            'salt', 'nonce', 'csrf', 'xsrf', 'cookie', 'session',
            // MFA / OTP
            'otp', 'mfa', '2fa', 'two_factor', 'twofactor', 'recovery_code',
            'backup_code', 'verification_code',
            // Hashes
            'hash',
            // Payment
            'card_number', 'cardnumber', 'card_no', 'cardno', 'cvv', 'cvc', 'ccv',
            'iban', 'swift', 'bic', 'routing', 'account_number', 'bank_account',
            // National IDs / PII
            'ssn', 'social_security', 'aadhaar', 'aadhar', 'pan_no', 'pan_number',
            'passport', 'drivers_license', 'driver_license', 'license_number',
            'tax_id', 'tin_number',
            // Misc secrets
            'client_secret', 'webhook_secret', 'app_secret',
        ];
        foreach ($containsPatterns as $needle) {
            if (str_contains($lower, $needle)) {
                return true;
            }
        }

        // Word-boundary patterns for short/ambiguous tokens (`pin`, `key`,
        // `code`) so we don't over-match common words like `zipcode`,
        // `keyword`, `pinned`, `promo_code`, etc.
        $strictPatterns = [
            '/(^|_)pin($|_)/',
            '/(^|_)key($|_)/',
            '/(^|_)auth_code($|_)/',
        ];
        foreach ($strictPatterns as $pattern) {
            if (preg_match($pattern, $lower) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Identifier / foreign-key columns we never want to leak in emails.
     * Covers `id`, any `*_id` / `*_uuid` / `*_ulid`, plus common polymorphic
     * id helpers (`*_type` is intentionally NOT ignored here since status-like
     * type fields may be meaningful).
     */
    private function isIdentifierField(string $field): bool
    {
        $lower = strtolower($field);
        if ($lower === 'id' || $lower === 'uuid' || $lower === 'ulid') {
            return true;
        }

        return (bool) preg_match('/_(id|uuid|ulid)$/', $lower);
    }

    /**
     * Detect whether a given model attribute is cast to a JSON / array /
     * object / collection shape. When true we skip the field entirely so the
     * email never contains raw JSON payloads.
     */
    private function isJsonCastField(Model $model, string $field): bool
    {
        $casts = method_exists($model, 'getCasts') ? $model->getCasts() : [];
        $cast = $casts[$field] ?? null;
        if (! is_string($cast) || $cast === '') {
            return false;
        }

        // Strip optional argument segment: e.g. "encrypted:array" -> "encrypted:array".
        $normalized = strtolower($cast);

        // Built-in Laravel JSON-family casts.
        $jsonCasts = [
            'array', 'json', 'object', 'collection',
            'encrypted:array', 'encrypted:json', 'encrypted:object', 'encrypted:collection',
        ];
        if (in_array($normalized, $jsonCasts, true)) {
            return true;
        }

        // Custom cast classes: AsArrayObject / AsCollection / AsEnumCollection / AsEncryptedArrayObject / AsEncryptedCollection.
        if (str_contains($normalized, 'asarrayobject')
            || str_contains($normalized, 'ascollection')
            || str_contains($normalized, 'asenumcollection')
            || str_contains($normalized, 'asencryptedarrayobject')
            || str_contains($normalized, 'asencryptedcollection')) {
            return true;
        }

        return false;
    }

    private function isImportantModelForEvent(Model $model, string $event): bool
    {
        $configured = config("push.model_activity.important_models.{$event}", []);
        if (! is_array($configured) || $configured === []) {
            return false;
        }

        return in_array(get_class($model), $configured, true);
    }

    private function hasImportantUpdateFieldChange(Model $model, array $changedFields): bool
    {
        $map = config('push.model_activity.important_update_fields', []);
        if (! is_array($map) || $map === []) {
            return true;
        }

        $globalFields = is_array($map['*'] ?? null) ? $map['*'] : [];
        $modelFields = is_array($map[get_class($model)] ?? null) ? $map[get_class($model)] : [];
        $importantFields = array_values(array_unique(array_merge($globalFields, $modelFields)));

        if ($importantFields === []) {
            return true;
        }

        return count(array_intersect($changedFields, $importantFields)) > 0;
    }

    private function isInCooldown(Model $model, string $event): bool
    {
        $seconds = max(0, (int) config('push.model_activity.cooldown_seconds', 120));
        if ($seconds <= 0) {
            return false;
        }

        $key = sprintf(
            'push:model-activity:%s:%s:%s',
            $event,
            get_class($model),
            (string) ($model->getKey() ?? 'na')
        );

        // add() returns false when key already exists (still in cooldown window).
        return ! Cache::add($key, now()->timestamp, $seconds);
    }

    private function notifyUsersBasedOnMode(string $title, string $message, array $data): void
    {
        try {
            if ((bool) config('push.model_activity.send_to_all', true)) {
                $this->notificationService->createAllUsersNotification(
                    title: $title,
                    message: $message,
                    type: 'system',
                    data: $data
                );
                return;
            }

            $roles = config('push.model_activity.roles', ['admin', 'super-admin']);
            if (! is_array($roles) || $roles === []) {
                return;
            }

            $this->notificationService->createRoleNotification(
                roles: $roles,
                title: $title,
                message: $message,
                type: 'system',
                data: $data
            );
        } catch (Throwable) {
            // Intentionally swallow to avoid breaking model writes.
        }
    }

    /**
     * Build an old/new diff for updated fields.
     *
     * Return shape:
     *   [
     *     'status' => ['old' => 'pending', 'new' => 'approved', 'type' => 'string'],
     *     'amount' => ['old' => 100, 'new' => 150, 'type' => 'number'],
     *   ]
     */
    private function buildChangeDiff(Model $model, array $fields): array
    {
        $diff = [];
        foreach ($fields as $field) {
            $oldRaw = $model->getOriginal($field);
            $newRaw = $model->getAttribute($field);

            // Runtime safety net: skip any field whose value resolves to a
            // structured payload (array / object / JSON string) — we never
            // want to dump that into the email.
            if ($this->looksLikeStructuredValue($oldRaw) || $this->looksLikeStructuredValue($newRaw)) {
                continue;
            }

            // Value-content safety net: if either side smells like a secret
            // (JWT, bearer, bcrypt, PEM key, card number, long hash, etc.)
            // drop the whole field so nothing sensitive is rendered.
            if ($this->looksLikeSecretValue($oldRaw) || $this->looksLikeSecretValue($newRaw)) {
                continue;
            }

            $diff[$field] = [
                'old' => $this->formatValueForDisplay($oldRaw),
                'new' => $this->formatValueForDisplay($newRaw),
                'type' => $this->inferValueType($newRaw ?? $oldRaw),
            ];
        }

        return $diff;
    }

    /**
     * Build a "new only" diff for freshly created fields.
     *
     * Return shape:
     *   [
     *     'name'  => ['new' => 'John', 'type' => 'string'],
     *     'email' => ['new' => 'j@x.io', 'type' => 'string'],
     *   ]
     */
    private function buildCreateDiff(Model $model, array $fields): array
    {
        $diff = [];
        foreach ($fields as $field) {
            $value = $model->getAttribute($field);

            // Runtime safety net: skip structured JSON-ish values.
            if ($this->looksLikeStructuredValue($value)) {
                continue;
            }

            // Value-content safety net: skip anything that looks like a
            // secret / token / card number / crypto material.
            if ($this->looksLikeSecretValue($value)) {
                continue;
            }

            $diff[$field] = [
                'new' => $this->formatValueForDisplay($value),
                'type' => $this->inferValueType($value),
            ];
        }

        return $diff;
    }

    /**
     * Heuristic: does $value look like a secret / token / credential / PII
     * we should never print in the email?
     *
     * Matches:
     *   - JWTs (three base64url segments separated by `.`)
     *   - Bearer prefixes
     *   - Bcrypt / Argon / PHPass / crypt(3) hash prefixes (`$2y$`, `$argon2...`, `$6$` etc.)
     *   - Long hex-only strings (>= 40 chars) — SHA-1, SHA-256, MD5 repeats
     *   - PEM / SSH private-key blocks
     *   - 13-19 digit card-like sequences (likely PAN numbers)
     *   - Long opaque high-entropy base64url strings (>= 40 chars, no spaces)
     */
    private function looksLikeSecretValue(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }
        $trim = trim($value);
        if ($trim === '') {
            return false;
        }

        $len = strlen($trim);

        // Short primitives are safe — skip the heavier regex work.
        if ($len < 20) {
            return false;
        }

        // Bearer / basic-auth prefixes.
        if (preg_match('/^(Bearer|Basic)\s+\S+/i', $trim)) {
            return true;
        }

        // JWT: header.payload.signature — all base64url.
        if (preg_match('/^eyJ[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+\.[A-Za-z0-9_\-]+$/', $trim)) {
            return true;
        }

        // PEM / OpenSSH / PGP blocks.
        if (preg_match('/-----BEGIN [A-Z0-9 ]+-----/', $trim)) {
            return true;
        }

        // Unix crypt / bcrypt / argon hash prefixes.
        if (preg_match('/^\$(2[aby]?|1|5|6|argon2[id]?|P|H|apr1)\$/', $trim)) {
            return true;
        }

        // Long hex-only digests (MD5=32, SHA1=40, SHA256=64, SHA512=128).
        if ($len >= 32 && preg_match('/^[a-f0-9]+$/i', $trim)) {
            return true;
        }

        // Card-like numeric strings (13-19 digits, optionally space/dash
        // separated). This matches Visa / Mastercard / Amex / Discover / etc.
        $digitsOnly = preg_replace('/[\s-]/', '', $trim) ?? '';
        if (preg_match('/^\d{13,19}$/', $digitsOnly)) {
            return true;
        }

        // Long opaque base64url / base64 blob with no whitespace —
        // likely an API key, refresh token, signed URL secret, etc.
        if ($len >= 40 && preg_match('/^[A-Za-z0-9_\-+\/=]+$/', $trim) && ! str_contains($trim, ' ')) {
            return true;
        }

        return false;
    }

    /**
     * Heuristic: is $value an array / object / JSON-looking string we should
     * never print in the email?
     */
    private function looksLikeStructuredValue(mixed $value): bool
    {
        if (is_array($value)) {
            return true;
        }

        if (is_object($value)
            && ! ($value instanceof \DateTimeInterface)
            && ! ($value instanceof \BackedEnum)
            && ! ($value instanceof \UnitEnum)
            && ! method_exists($value, '__toString')) {
            return true;
        }

        if (is_string($value)) {
            $trimmed = ltrim($value);
            if ($trimmed === '') {
                return false;
            }
            // Quick prefix check — only then pay the json_decode cost.
            $first = $trimmed[0];
            if (($first === '{' || $first === '[') && strlen($trimmed) <= 65535) {
                $decoded = json_decode($trimmed, true);
                if ((is_array($decoded)) && json_last_error() === JSON_ERROR_NONE) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Convert any scalar / array / object value into a safe, human-readable
     * string for display in the email. Never leaks raw objects or long blobs.
     *
     * - Strips HTML tags / comments (for rich-text fields like bio, description).
     * - Collapses whitespace.
     * - Truncates to the first 50 words with an ellipsis.
     */
    private function formatValueForDisplay(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('d M, Y h:i A');
        }

        if (is_array($value)) {
            $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return $this->sanitizeAndLimitWords($encoded ?: '[array]', 50);
        }

        if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                return $this->sanitizeAndLimitWords((string) $value, 50);
            }
            return '[' . class_basename($value) . ']';
        }

        $string = (string) $value;
        if ($string === '') {
            return '(empty)';
        }

        return $this->sanitizeAndLimitWords($string, 50);
    }

    /**
     * Strip any HTML tags / comments / script-style blocks, collapse
     * whitespace, and cap the result at the first `$maxWords` words.
     *
     * Safe for rich-text field values (bio, description, comment, etc.)
     * that may contain markup injected from a WYSIWYG editor.
     */
    private function sanitizeAndLimitWords(string $value, int $maxWords): string
    {
        // Remove HTML comments, script/style blocks first (strip_tags keeps their contents).
        $clean = preg_replace('/<!--.*?-->/s', '', $value) ?? $value;
        $clean = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $clean) ?? $clean;

        // Strip all remaining HTML tags.
        $clean = strip_tags($clean);

        // Decode HTML entities so things like &amp; / &nbsp; display naturally.
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Collapse all whitespace (tabs, newlines, multiple spaces) into single spaces.
        $clean = preg_replace('/\s+/u', ' ', $clean) ?? $clean;
        $clean = trim($clean);

        if ($clean === '') {
            return '(empty)';
        }

        // Word-based truncation to the first $maxWords words.
        $words = preg_split('/\s+/u', $clean, $maxWords + 1) ?: [];
        if (count($words) > $maxWords) {
            $words = array_slice($words, 0, $maxWords);
            return implode(' ', $words) . '…';
        }

        return $clean;
    }

    /**
     * Infer a coarse value type used by the Blade to choose the right badge.
     */
    private function inferValueType(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value) || is_float($value)) {
            return 'number';
        }
        if ($value instanceof \DateTimeInterface) {
            return 'datetime';
        }
        if (is_array($value) || is_object($value)) {
            return 'json';
        }
        return 'string';
    }

    /**
     * Decide whether a creation event should trigger a security email.
     * Fires when the model is explicitly force-listed, the global
     * `email_on_all_creations` flag is on, or it's an important event.
     */
    private function shouldEmailOnCreation(Model $model, bool $isImportantEvent): bool
    {
        if ($this->isEmailForcedModel($model)) {
            return true;
        }

        if ((bool) config('push.model_activity.email_on_all_creations', false)) {
            return true;
        }

        return $isImportantEvent;
    }

    /**
     * Models that MUST always receive a security email on create & update,
     * regardless of the `email_on_all_*` toggles.
     */
    private function isEmailForcedModel(Model $model): bool
    {
        $forced = config('push.model_activity.email_force_models', []);
        if (! is_array($forced) || $forced === []) {
            return false;
        }

        return in_array(get_class($model), $forced, true);
    }

    /**
     * Queue a security-alert email to the account owner whenever an important
     * model is created or updated. Failures are logged but never allowed to
     * break the underlying write that triggered the event.
     */
    private function dispatchSecurityAlertEmail(
        Model $model,
        string $modelLabel,
        string|int|null $modelKey,
        array $changedFields,
        ?object $actor,
        string $actorName,
        string $event = 'updated',
    ): void {
        try {
            $recipient = $this->resolveSecurityAlertRecipient($model, $actor);
            if ($recipient === null || empty($recipient->email)) {
                return;
            }

            $request = app()->bound('request') ? request() : null;
            $ipAddress = $request?->ip();
            $userAgent = $request?->userAgent();

            Mail::to($recipient->email)->queue(new ModelUpdateSecurityAlert(
                user: $recipient,
                modelLabel: $modelLabel,
                modelId: $modelKey,
                changedFields: $changedFields,
                actorName: $actorName,
                ipAddress: $ipAddress,
                userAgent: $userAgent,
                eventTime: now()->format('d M, Y h:i A'),
                event: $event,
            ));
        } catch (Throwable $e) {
            Log::warning('Failed to queue model activity security alert email', [
                'event' => $event,
                'model' => get_class($model),
                'model_id' => $modelKey,
                'actor_user_id' => $actor?->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Figure out which user should receive the security alert for a change.
     *
     * Preference order:
     *   1. The record's owning user (user_id / userId on the model).
     *   2. If the model itself is a User, the User instance.
     *   3. The authenticated actor who made the change.
     */
    private function resolveSecurityAlertRecipient(Model $model, ?object $actor): ?object
    {
        if ($model instanceof User) {
            return $model;
        }

        $ownerId = null;
        foreach (['user_id', 'userId', 'owner_id'] as $attr) {
            $value = $model->getAttribute($attr);
            if (! empty($value)) {
                $ownerId = $value;
                break;
            }
        }

        if ($ownerId !== null) {
            $owner = User::query()->find($ownerId);
            if ($owner !== null) {
                return $owner;
            }
        }

        return $actor instanceof User ? $actor : null;
    }
}
