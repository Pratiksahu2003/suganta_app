<?php

namespace App\Services\V4\Google;

use App\Services\V4\Support\RedisApiCacheService;
use Illuminate\Http\UploadedFile;

class GoogleSyncService
{
    public function __construct(
        private readonly GoogleApiService $googleApiService,
        private readonly RedisApiCacheService $cacheService
    ) {}

    public function syncCalendar(int $userId, string $accessToken, int $maxResults = 20): array
    {
        return $this->cacheService->remember(
            $this->cacheKey($userId, $accessToken, 'calendar', ['maxResults' => $maxResults]),
            $this->calendarTtl(),
            fn (): array => $this->googleApiService->listCalendarEvents($accessToken, $maxResults)
        );
    }

    public function syncYoutube(int $userId, string $accessToken, int $maxResults = 10): array
    {
        return $this->cacheService->remember(
            $this->cacheKey($userId, $accessToken, 'youtube', ['maxResults' => $maxResults]),
            $this->youtubeTtl(),
            fn (): array => $this->googleApiService->listYoutubeChannels($accessToken, $maxResults)
        );
    }

    public function syncDrive(int $userId, string $accessToken, int $pageSize = 50, ?string $orderBy = null): array
    {
        return $this->cacheService->remember(
            $this->cacheKey($userId, $accessToken, 'drive', ['pageSize' => $pageSize, 'orderBy' => $orderBy]),
            $this->driveTtl(),
            fn (): array => $this->googleApiService->listDriveFiles($accessToken, $pageSize, $orderBy)
        );
    }

    public function createCalendarEvent(string $accessToken, array $payload): array
    {
        return $this->googleApiService->createCalendarEvent($accessToken, $payload);
    }

    public function updateCalendarEvent(string $accessToken, string $eventId, array $payload): array
    {
        return $this->googleApiService->updateCalendarEvent($accessToken, $eventId, $payload);
    }

    public function deleteCalendarEvent(string $accessToken, string $eventId): void
    {
        $this->googleApiService->deleteCalendarEvent($accessToken, $eventId);
    }

    public function createDriveFolder(string $accessToken, string $name, ?string $parentId = null): array
    {
        return $this->googleApiService->createDriveFolder($accessToken, $name, $parentId);
    }

    public function renameDriveFile(string $accessToken, string $fileId, string $name): array
    {
        return $this->googleApiService->renameDriveFile($accessToken, $fileId, $name);
    }

    public function deleteDriveFile(string $accessToken, string $fileId): void
    {
        $this->googleApiService->deleteDriveFile($accessToken, $fileId);
    }

    public function getCalendarEvent(string $accessToken, string $eventId): array
    {
        return $this->googleApiService->getCalendarEvent($accessToken, $eventId);
    }

    public function searchDriveFiles(string $accessToken, ?string $query, int $pageSize = 50, ?string $pageToken = null, ?string $orderBy = null): array
    {
        return $this->googleApiService->searchDriveFiles($accessToken, $query, $pageSize, $pageToken, $orderBy);
    }

    public function uploadDriveFile(string $accessToken, UploadedFile $file, ?string $name = null, ?string $parentId = null, ?string $mimeType = null): array
    {
        return $this->googleApiService->uploadDriveFile($accessToken, $file, $name, $parentId, $mimeType);
    }

    public function moveDriveFile(string $accessToken, string $fileId, string $newParentId, ?string $removeParentId = null): array
    {
        return $this->googleApiService->moveDriveFile($accessToken, $fileId, $newParentId, $removeParentId);
    }

    public function shareDriveFile(
        string $accessToken,
        string $fileId,
        string $email,
        string $role,
        string $type = 'user',
        bool $sendNotificationEmail = true
    ): array {
        return $this->googleApiService->shareDriveFile(
            $accessToken,
            $fileId,
            $email,
            $role,
            $type,
            $sendNotificationEmail
        );
    }

    private function cacheKey(int $userId, string $accessToken, string $resource, array $params): string
    {
        ksort($params);
        $watchVersion = $this->cacheService->readVersion('google:watch:user:'.$userId.':'.$resource);

        return implode(':', [
            'google',
            (string) $userId,
            $resource,
            'v'.$watchVersion,
            sha1($accessToken),
            sha1(json_encode($params, JSON_THROW_ON_ERROR)),
        ]);
    }

    private function calendarTtl(): int
    {
        return max(30, (int) config('cache.v4_google_calendar_ttl_seconds', 60));
    }

    private function youtubeTtl(): int
    {
        return max(30, (int) config('cache.v4_google_youtube_ttl_seconds', 120));
    }

    private function driveTtl(): int
    {
        return max(30, (int) config('cache.v4_google_drive_ttl_seconds', 90));
    }
}
