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
            $providedFields = array_values(array_filter(
                array_keys($model->getAttributes()),
                fn (string $field): bool => ! $this->isIgnoredField($model, $field)
            ));

            $this->dispatchSecurityAlertEmail(
                model: $model,
                modelLabel: $modelLabel,
                modelKey: $modelKey,
                changedFields: $providedFields,
                actor: $actor,
                actorName: $actorName,
                event: 'created',
            );
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
        $changedFields = array_values(array_filter(
            array_keys($changed),
            fn (string $field): bool => ! $this->isIgnoredField($model, $field)
        ));
        if ($changedFields === []) {
            return;
        }

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
            && $this->hasImportantUpdateFieldChange($model, $changedFields);

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
                    'changed_fields' => $changedFields,
                ]
            );
        }

        // Security email: fires on EVERY non-system update (token/session/log
        // models & fields were already filtered out above). When
        // `email_on_all_updates` is disabled, fall back to the stricter
        // "important event" / force-list behaviour.
        $emailOnAllUpdates = (bool) config('push.model_activity.email_on_all_updates', true);
        if ($emailOnAllUpdates || $isImportantEvent || $this->isEmailForcedModel($model)) {
            $this->dispatchSecurityAlertEmail(
                model: $model,
                modelLabel: $modelLabel,
                modelKey: $modelKey,
                changedFields: $changedFields,
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
