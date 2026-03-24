<?php

namespace App\Http\Controllers\Api\V4\Google;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V4\Google\CalendarEventUpsertRequest;
use App\Http\Requests\Api\V4\Google\ConnectGoogleRequest;
use App\Http\Requests\Api\V4\Google\GoogleWatchRequest;
use App\Http\Requests\Api\V4\Google\DriveFileRenameRequest;
use App\Http\Requests\Api\V4\Google\DriveFolderCreateRequest;
use App\Http\Requests\Api\V4\Google\DriveShareRequest;
use App\Http\Requests\Api\V4\Google\DriveUploadRequest;
use App\Http\Requests\Api\V4\Google\OAuthCodeExchangeRequest;
use App\Http\Requests\Api\V4\Google\SyncGoogleRequest;
use App\Models\GoogleWatchChannel;
use App\Models\User;
use App\Services\V4\Google\GoogleSyncService;
use App\Services\V4\Google\GoogleTokenService;
use App\Services\V4\Google\GoogleWatchService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use RuntimeException;
use Throwable;

class GoogleSyncController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly GoogleSyncService $googleSyncService,
        private readonly GoogleTokenService $googleTokenService,
        private readonly GoogleWatchService $googleWatchService
    ) {}

    public function connect(ConnectGoogleRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $connectedUser = $this->googleTokenService->connect(
            $user,
            (string) $request->string('refresh_token'),
            $request->input('access_token'),
            $request->integer('expires_in') ?: null,
            $request->input('google_email'),
            $request->input('google_calendar_id')
        );

        return $this->success('Google account connected successfully.', $this->googleTokenService->status($connectedUser));
    }

    public function exchangeCode(OAuthCodeExchangeRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $data = $this->googleTokenService->exchangeAuthorizationCode(
                $user,
                (string) $request->string('code'),
                $request->input('redirect_uri')
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Google authorization code exchange failed.', $exception->getMessage());
        }

        return $this->success('Google authorization code exchanged successfully.', $data);
    }

    public function disconnect(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $disconnectedUser = $this->googleTokenService->disconnect($user);

        return $this->success('Google account disconnected successfully.', $this->googleTokenService->status($disconnectedUser));
    }

    public function status(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $watchChannels = GoogleWatchChannel::query()
            ->where('user_id', $user->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get([
                'id',
                'resource_type',
                'channel_id',
                'status',
                'expires_at',
                'last_notification_at',
                'last_message_number',
            ]);

        return $this->success('Google connection status retrieved successfully.', [
            ...$this->googleTokenService->status($user),
            'urls' => $this->resolveGoogleUrls(),
            'watch_channels' => $watchChannels,
        ]);
    }

    public function urls(): JsonResponse
    {
        return $this->success('Google URLs retrieved successfully.', $this->resolveGoogleUrls());
    }

    public function refreshToken(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $accessToken = $this->googleTokenService->refreshAccessToken($user);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Google token refresh failed.', $exception->getMessage());
        }

        return $this->success('Google access token refreshed successfully.', [
            'access_token' => $accessToken,
            'status' => $this->googleTokenService->status($user->refresh()),
        ]);
    }

    public function syncAll(SyncGoogleRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $sync = $request->input('sync', ['calendar', 'youtube', 'drive']);

        $payload = [];

        try {
            $token = $this->googleTokenService->getValidAccessToken(
                $user,
                $request->input('access_token')
            );

            if (in_array('calendar', $sync, true)) {
                $payload['calendar'] = $this->googleSyncService->syncCalendar(
                    (int) $user->id,
                    $token,
                    (int) $request->input('calendar.max_results', 20)
                );
            }

            if (in_array('youtube', $sync, true)) {
                $payload['youtube'] = $this->googleSyncService->syncYoutube(
                    (int) $user->id,
                    $token,
                    (int) $request->input('youtube.max_results', 10)
                );
            }

            if (in_array('drive', $sync, true)) {
                $payload['drive'] = $this->googleSyncService->syncDrive(
                    (int) $user->id,
                    $token,
                    (int) $request->input('drive.page_size', 50),
                    $request->input('drive.order_by')
                );
            }
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Google sync failed.', $exception->getMessage());
        }

        return $this->success('Google resources synced successfully.', $payload);
    }

    public function calendarEvents(SyncGoogleRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken(
                $user,
                $request->input('access_token')
            );
            $data = $this->googleSyncService->syncCalendar(
                (int) $user->id,
                $token,
                (int) $request->input('calendar.max_results', 20)
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Calendar sync failed.', $exception->getMessage());
        }

        return $this->success('Google Calendar synced successfully.', $data);
    }

    public function youtubeChannels(SyncGoogleRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken(
                $user,
                $request->input('access_token')
            );
            $data = $this->googleSyncService->syncYoutube(
                (int) $user->id,
                $token,
                (int) $request->input('youtube.max_results', 10)
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('YouTube sync failed.', $exception->getMessage());
        }

        return $this->success('Google YouTube synced successfully.', $data);
    }

    public function driveFiles(SyncGoogleRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken(
                $user,
                $request->input('access_token')
            );
            $data = $this->googleSyncService->syncDrive(
                (int) $user->id,
                $token,
                (int) $request->input('drive.page_size', 50),
                $request->input('drive.order_by')
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Drive sync failed.', $exception->getMessage());
        }

        return $this->success('Google Drive synced successfully.', $data);
    }

    public function createCalendarEvent(CalendarEventUpsertRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user, $request->input('access_token'));
            $data = $this->googleSyncService->createCalendarEvent($token, $this->calendarPayload($request));
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Create calendar event failed.', $exception->getMessage());
        }

        return $this->success('Calendar event created successfully.', $data);
    }

    public function updateCalendarEvent(CalendarEventUpsertRequest $request, string $eventId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user, $request->input('access_token'));
            $data = $this->googleSyncService->updateCalendarEvent($token, $eventId, $this->calendarPayload($request));
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Update calendar event failed.', $exception->getMessage());
        }

        return $this->success('Calendar event updated successfully.', $data);
    }

    public function getCalendarEvent(string $eventId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user);
            $data = $this->googleSyncService->getCalendarEvent($token, $eventId);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Get calendar event failed.', $exception->getMessage());
        }

        return $this->success('Calendar event fetched successfully.', $data);
    }

    public function deleteCalendarEvent(string $eventId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user);
            $this->googleSyncService->deleteCalendarEvent($token, $eventId);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Delete calendar event failed.', $exception->getMessage());
        }

        return $this->success('Calendar event deleted successfully.');
    }

    public function createDriveFolder(DriveFolderCreateRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user, $request->input('access_token'));
            $data = $this->googleSyncService->createDriveFolder(
                $token,
                (string) $request->string('name'),
                $request->input('parent_id')
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Create drive folder failed.', $exception->getMessage());
        }

        return $this->success('Drive folder created successfully.', $data);
    }

    public function renameDriveFile(DriveFileRenameRequest $request, string $fileId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user, $request->input('access_token'));
            $data = $this->googleSyncService->renameDriveFile(
                $token,
                $fileId,
                (string) $request->string('name')
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Rename drive file failed.', $exception->getMessage());
        }

        return $this->success('Drive file renamed successfully.', $data);
    }

    public function deleteDriveFile(string $fileId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user);
            $this->googleSyncService->deleteDriveFile($token, $fileId);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Delete drive file failed.', $exception->getMessage());
        }

        return $this->success('Drive file deleted successfully.');
    }

    public function searchDriveFiles(SyncGoogleRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user, $request->input('access_token'));
            $data = $this->googleSyncService->searchDriveFiles(
                $token,
                $request->input('drive.query'),
                (int) $request->input('drive.page_size', 50),
                $request->input('drive.page_token'),
                $request->input('drive.order_by')
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Drive search failed.', $exception->getMessage());
        }

        return $this->success('Drive files fetched successfully.', $data);
    }

    public function uploadDriveFile(DriveUploadRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user, $request->input('access_token'));
            $data = $this->googleSyncService->uploadDriveFile(
                $token,
                $request->file('file'),
                $request->input('name'),
                $request->input('parent_id'),
                $request->input('mime_type')
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Drive upload failed.', $exception->getMessage());
        }

        return $this->success('Drive file uploaded successfully.', $data);
    }

    public function moveDriveFile(SyncGoogleRequest $request, string $fileId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'new_parent_id' => ['required', 'string', 'max:255'],
            'remove_parent_id' => ['nullable', 'string', 'max:255'],
            'access_token' => ['nullable', 'string', 'min:20'],
        ]);

        try {
            $token = $this->googleTokenService->getValidAccessToken($user, $request->input('access_token'));
            $data = $this->googleSyncService->moveDriveFile(
                $token,
                $fileId,
                (string) $request->string('new_parent_id'),
                $request->input('remove_parent_id')
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Drive move failed.', $exception->getMessage());
        }

        return $this->success('Drive file moved successfully.', $data);
    }

    public function shareDriveFile(DriveShareRequest $request, string $fileId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user, $request->input('access_token'));
            $data = $this->googleSyncService->shareDriveFile(
                $token,
                $fileId,
                (string) $request->string('email'),
                (string) $request->string('role'),
                (string) $request->input('type', 'user'),
                (bool) $request->boolean('send_notification_email', true)
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Drive share failed.', $exception->getMessage());
        }

        return $this->success('Drive file shared successfully.', $data);
    }

    public function startWatch(GoogleWatchRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user, $request->input('access_token'));
            $channel = $this->googleWatchService->startWatch(
                $user,
                $token,
                (string) $request->string('resource_type'),
                (int) $request->input('ttl_seconds', 3600)
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Google watch start failed.', $exception->getMessage());
        }

        return $this->created([
            'channel' => $channel,
        ], 'Google watch channel created successfully.');
    }

    public function stopWatch(string $channelId): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $token = $this->googleTokenService->getValidAccessToken($user);
            $this->googleWatchService->stopWatch($user, $channelId, $token);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), $exception->getCode() ?: 400);
        } catch (Throwable $exception) {
            return $this->serverError('Google watch stop failed.', $exception->getMessage());
        }

        return $this->success('Google watch channel stopped successfully.');
    }

    public function webhook(\Illuminate\Http\Request $request): JsonResponse
    {
        try {
            $headers = collect($request->headers->all())
                ->map(static fn ($value): string => is_array($value) ? (string) ($value[0] ?? '') : (string) $value)
                ->all();

            $this->googleWatchService->handleWebhook($headers);
        } catch (Throwable $exception) {
            return response()->json(['message' => 'Webhook processing error.'], 200);
        }

        return response()->json(['message' => 'Webhook received.'], 200);
    }

    private function resolveGoogleUrls(): array
    {
        return [
            'webhook_url' => (string) config('services.google.webhook_url'),
            'return_url' => (string) config('services.google.redirect_uri'),
            'oauth_exchange_endpoint' => url('/api/v4/google/oauth/exchange-code'),
            'webhook_endpoint' => url('/api/v4/google/webhook'),
        ];
    }

    private function calendarPayload(CalendarEventUpsertRequest $request): array
    {
        $timezone = (string) ($request->input('timezone') ?: config('app.timezone', 'UTC'));

        return [
            'summary' => (string) $request->string('summary'),
            'description' => $request->input('description'),
            'location' => $request->input('location'),
            'start' => [
                'dateTime' => (string) $request->date('start')?->toIso8601String(),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => (string) $request->date('end')?->toIso8601String(),
                'timeZone' => $timezone,
            ],
            'attendees' => collect((array) $request->input('attendees', []))
                ->map(static fn (array $attendee): array => array_filter([
                    'email' => $attendee['email'] ?? null,
                    'displayName' => $attendee['display_name'] ?? null,
                    'optional' => $attendee['optional'] ?? null,
                ], static fn ($value) => $value !== null))
                ->values()
                ->all(),
            'reminders' => [
                'useDefault' => (bool) $request->input('reminders.use_default', true),
                'overrides' => $request->input('reminders.overrides', []),
            ],
            'conferenceData' => $request->boolean('with_google_meet')
                ? [
                    'createRequest' => [
                        'requestId' => (string) \Illuminate\Support\Str::uuid(),
                        'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                    ],
                ]
                : null,
        ];
    }
}
