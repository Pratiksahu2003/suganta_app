<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
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
        if (! $this->shouldNotifyFor($model)) {
            return;
        }

        $actor = Auth::user();
        $actorName = $actor?->name ?? 'System';
        $modelLabel = class_basename($model);
        $modelKey = $model->getKey();

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

    public function onUpdated(Model $model): void
    {
        if (! $this->shouldNotifyFor($model)) {
            return;
        }

        $changed = Arr::except($model->getChanges(), ['updated_at']);
        if ($changed === []) {
            return;
        }

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
}
