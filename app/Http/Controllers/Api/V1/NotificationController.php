<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Notification;
use App\Models\User;
use App\Services\FirebasePushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NotificationController extends BaseApiController
{
    public function __construct(private readonly FirebasePushService $firebasePushService)
    {
    }

    /**
     * Get all notifications for the authenticated user with pagination.
     *
     * Query params:
     * - per_page: Items per page (default 15, max 50)
     * - page: Page number
     * - filter: all|read|unread (default: all)
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Notification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->orderByDesc('created_at');

        $filter = $request->string('filter', 'all')->toString();
        if ($filter === 'read') {
            $query->read();
        } elseif ($filter === 'unread') {
            $query->unread();
        }

        $perPage = min((int) $request->get('per_page', 15), 50);
        $notifications = $query->paginate($perPage);

        $formatted = $notifications->getCollection()->map(
            fn (Notification $n) => $this->formatNotification($n)
        );

        return $this->success('Notifications retrieved successfully.', [
            'data' => $formatted->values()->all(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
                'from' => $notifications->firstItem(),
                'to' => $notifications->lastItem(),
            ],
            'links' => [
                'first' => $notifications->url(1),
                'last' => $notifications->url($notifications->lastPage()),
                'prev' => $notifications->previousPageUrl(),
                'next' => $notifications->nextPageUrl(),
            ],
        ]);
    }

    public function registerPushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:2048'],
            'platform' => ['nullable', 'string', 'in:android,ios,web,unknown'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $subscription = $this->firebasePushService->registerToken(
            $user,
            $validated['token'],
            $validated['platform'] ?? 'unknown',
            $validated['device_name'] ?? null
        );

        Log::channel('firebase_push')->info('api.push_token.register.success', [
            'user_id' => $user->id,
            'platform' => $validated['platform'] ?? 'unknown',
            'device_name' => $validated['device_name'] ?? null,
            'token_hash' => substr(hash('sha256', $validated['token']), 0, 16),
        ]);

        return $this->success('Push token registered successfully.', [
            'push_subscription' => $subscription,
        ]);
    }

    public function unregisterPushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:2048'],
        ]);

        /** @var User $user */
        $user = Auth::user();
        $subscription = $this->firebasePushService->removeToken($user, $validated['token']);

        Log::channel('firebase_push')->info('api.push_token.unregister.success', [
            'user_id' => $user->id,
            'token_hash' => substr(hash('sha256', $validated['token']), 0, 16),
        ]);

        return $this->success('Push token removed successfully.', [
            'push_subscription' => $subscription,
        ]);
    }

    /**
     * Format notification for API response (resource-based structure).
     */
    protected function formatNotification(Notification $notification): array
    {
        $data = $notification->data ?? [];

        return [
            'id' => $notification->id,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'type' => $data['type'] ?? null,
            'priority' => $data['priority'] ?? 'normal',
            'action_url' => $data['action_url'] ?? null,
            'resource_type' => $data['resource_type'] ?? null,
            'resource_id' => $data['resource_id'] ?? null,
            'action' => $data['action'] ?? null,
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at->toIso8601String(),
        ];
    }
}
