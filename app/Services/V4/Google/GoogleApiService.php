<?php

namespace App\Services\V4\Google;

use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class GoogleApiService
{
    public function __construct(private readonly HttpFactory $http) {}

    public function listCalendarEvents(string $accessToken, int $maxResults = 20): array
    {
        return $this->requestJson(
            $this->calendarBaseUrl().'/calendars/primary/events',
            $accessToken,
            [
                'maxResults' => min(100, max(1, $maxResults)),
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'timeMin' => now()->toIso8601ZuluString(),
            ]
        );
    }

    public function listYoutubeChannels(string $accessToken, int $maxResults = 10): array
    {
        return $this->requestJson(
            $this->youtubeBaseUrl().'/channels',
            $accessToken,
            [
                'part' => 'snippet,statistics,contentDetails',
                'mine' => 'true',
                'maxResults' => min(50, max(1, $maxResults)),
            ]
        );
    }

    public function listDriveFiles(string $accessToken, int $pageSize = 50, ?string $orderBy = null): array
    {
        $query = [
            'pageSize' => min(1000, max(1, $pageSize)),
            'fields' => 'files(id,name,mimeType,modifiedTime,size,webViewLink),nextPageToken',
            'q' => 'trashed=false',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ];

        if ($orderBy) {
            $query['orderBy'] = $orderBy;
        }

        return $this->requestJson(
            $this->driveBaseUrl().'/files',
            $accessToken,
            $query
        );
    }

    public function getCalendarEvent(string $accessToken, string $eventId): array
    {
        return $this->requestJson(
            $this->calendarBaseUrl().'/calendars/primary/events/'.$eventId,
            $accessToken
        );
    }

    public function createCalendarEvent(string $accessToken, array $payload): array
    {
        return $this->requestJsonByMethod(
            'post',
            $this->calendarBaseUrl().'/calendars/primary/events',
            $accessToken,
            [],
            $payload
        );
    }

    public function updateCalendarEvent(string $accessToken, string $eventId, array $payload): array
    {
        return $this->requestJsonByMethod(
            'patch',
            $this->calendarBaseUrl().'/calendars/primary/events/'.$eventId,
            $accessToken,
            [],
            $payload
        );
    }

    public function deleteCalendarEvent(string $accessToken, string $eventId): void
    {
        $this->requestJsonByMethod(
            'delete',
            $this->calendarBaseUrl().'/calendars/primary/events/'.$eventId,
            $accessToken
        );
    }

    public function createDriveFolder(string $accessToken, string $name, ?string $parentId = null): array
    {
        $payload = [
            'name' => $name,
            'mimeType' => 'application/vnd.google-apps.folder',
        ];

        if ($parentId) {
            $payload['parents'] = [$parentId];
        }

        return $this->requestJsonByMethod(
            'post',
            $this->driveBaseUrl().'/files',
            $accessToken,
            [],
            $payload
        );
    }

    public function searchDriveFiles(
        string $accessToken,
        ?string $query,
        int $pageSize = 50,
        ?string $pageToken = null,
        ?string $orderBy = null
    ): array {
        $googleQuery = $query ?: 'trashed=false';
        $params = [
            'q' => $googleQuery,
            'pageSize' => min(1000, max(1, $pageSize)),
            'fields' => 'files(id,name,mimeType,modifiedTime,size,webViewLink,parents),nextPageToken',
            'supportsAllDrives' => true,
            'includeItemsFromAllDrives' => true,
        ];

        if ($pageToken) {
            $params['pageToken'] = $pageToken;
        }

        if ($orderBy) {
            $params['orderBy'] = $orderBy;
        }

        return $this->requestJson(
            $this->driveBaseUrl().'/files',
            $accessToken,
            $params
        );
    }

    public function uploadDriveFile(string $accessToken, UploadedFile $file, ?string $name = null, ?string $parentId = null, ?string $mimeType = null): array
    {
        $metadata = ['name' => $name ?: $file->getClientOriginalName()];
        if ($parentId) {
            $metadata['parents'] = [$parentId];
        }

        $boundary = '--------------------------'.md5((string) microtime(true));
        $fileMimeType = $mimeType ?: ($file->getMimeType() ?: 'application/octet-stream');
        $body = "--{$boundary}\r\n";
        $body .= "Content-Type: application/json; charset=UTF-8\r\n\r\n";
        $body .= json_encode($metadata, JSON_THROW_ON_ERROR)."\r\n";
        $body .= "--{$boundary}\r\n";
        $body .= "Content-Type: {$fileMimeType}\r\n\r\n";
        $body .= file_get_contents($file->getRealPath()) ?: '';
        $body .= "\r\n--{$boundary}--";
        $normalizedToken = $this->normalizeAccessToken($accessToken);
        $this->assertValidAccessToken($normalizedToken);
        $client = $this->googleClient($normalizedToken);

        Log::info('Google API Request', [
            'method' => 'POST',
            'url' => 'https://www.googleapis.com/upload/drive/v3/files',
            'query' => ['uploadType' => 'multipart'],
            'token_present' => $normalizedToken !== '',
            'token_preview' => mb_substr($normalizedToken, 0, 20).'...',
        ]);

        $response = $client
            ->send('POST', 'https://www.googleapis.com/upload/drive/v3/files', [
                'query' => ['uploadType' => 'multipart'],
                'headers' => ['Content-Type' => "multipart/related; boundary={$boundary}"],
                'body' => $body,
            ]);

        Log::info('Google API Response', [
            'method' => 'POST',
            'url' => 'https://www.googleapis.com/upload/drive/v3/files',
            'status' => $response->status(),
            'is_json' => (string) $response->header('content-type', ''),
        ]);

        $this->logGoogleApiResponse('POST', 'https://www.googleapis.com/upload/drive/v3/files', $response->status(), [
            'query' => ['uploadType' => 'multipart'],
            'request' => [
                'name' => $metadata['name'] ?? null,
                'parent_id' => $parentId,
                'mime_type' => $fileMimeType,
                'size_bytes' => $file->getSize(),
            ],
            'response' => $this->responseBodyForLog($response),
        ]);

        try {
            $response->throw();
        } catch (RequestException $exception) {
            $bodyJson = $response->json();
            $message = is_array($bodyJson)
                ? (string) data_get($bodyJson, 'error.message', 'Google Drive upload failed.')
                : 'Google Drive upload failed.';

            throw new \RuntimeException($message, $response->status(), $exception);
        }

        return $response->json() ?? [];
    }

    public function moveDriveFile(string $accessToken, string $fileId, string $newParentId, ?string $removeParentId = null): array
    {
        return $this->requestJsonByMethod(
            'patch',
            $this->driveBaseUrl().'/files/'.$fileId,
            $accessToken,
            array_filter([
                'addParents' => $newParentId,
                'removeParents' => $removeParentId,
            ]),
            []
        );
    }

    public function shareDriveFile(
        string $accessToken,
        string $fileId,
        string $email,
        string $role,
        string $type = 'user',
        bool $sendNotificationEmail = true
    ): array {
        return $this->requestJsonByMethod(
            'post',
            $this->driveBaseUrl().'/files/'.$fileId.'/permissions',
            $accessToken,
            ['sendNotificationEmail' => $sendNotificationEmail],
            [
                'type' => $type,
                'role' => $role,
                'emailAddress' => $email,
            ]
        );
    }

    public function watchCalendarEvents(
        string $accessToken,
        string $channelId,
        string $address,
        string $token,
        int $ttlSeconds
    ): array {
        return $this->requestJsonByMethod(
            'post',
            $this->calendarBaseUrl().'/calendars/primary/events/watch',
            $accessToken,
            [],
            [
                'id' => $channelId,
                'type' => 'web_hook',
                'address' => $address,
                'token' => $token,
                'params' => ['ttl' => (string) $ttlSeconds],
            ]
        );
    }

    public function watchDriveChanges(
        string $accessToken,
        string $channelId,
        string $address,
        string $token,
        int $ttlSeconds
    ): array {
        $startToken = $this->requestJson(
            $this->driveBaseUrl().'/changes/startPageToken',
            $accessToken,
            ['supportsAllDrives' => true]
        );
        $pageToken = (string) data_get($startToken, 'startPageToken', '');
        if ($pageToken === '') {
            throw new \RuntimeException('Unable to get Google Drive startPageToken.', 500);
        }

        return $this->requestJsonByMethod(
            'post',
            $this->driveBaseUrl().'/changes/watch',
            $accessToken,
            [
                'pageToken' => $pageToken,
                'supportsAllDrives' => true,
                'includeItemsFromAllDrives' => true,
            ],
            [
                'id' => $channelId,
                'type' => 'web_hook',
                'address' => $address,
                'token' => $token,
                'params' => ['ttl' => (string) $ttlSeconds],
            ]
        );
    }

    public function stopWatchChannel(string $accessToken, string $channelId, string $resourceId): array
    {
        return $this->requestJsonByMethod(
            'post',
            'https://www.googleapis.com/calendar/v3/channels/stop',
            $accessToken,
            [],
            [
                'id' => $channelId,
                'resourceId' => $resourceId,
            ]
        );
    }

    public function renameDriveFile(string $accessToken, string $fileId, string $name): array
    {
        return $this->requestJsonByMethod(
            'patch',
            $this->driveBaseUrl().'/files/'.$fileId,
            $accessToken,
            [],
            ['name' => $name]
        );
    }

    public function deleteDriveFile(string $accessToken, string $fileId): void
    {
        $this->requestJsonByMethod(
            'delete',
            $this->driveBaseUrl().'/files/'.$fileId,
            $accessToken
        );
    }

    private function requestJson(string $url, string $accessToken, array $query = []): array
    {
        return $this->requestJsonByMethod('get', $url, $accessToken, $query);
    }

    private function requestJsonByMethod(
        string $method,
        string $url,
        string $accessToken,
        array $query = [],
        array $payload = []
    ): array {
        $normalizedToken = $this->normalizeAccessToken($accessToken);
        $this->assertValidAccessToken($normalizedToken);
        $client = $this->googleClient($normalizedToken);
        $methodUpper = strtoupper($method);

        Log::info('Google API Request', [
            'method' => $methodUpper,
            'url' => $url,
            'query' => $query,
            'token_present' => $normalizedToken !== '',
            'token_preview' => mb_substr($normalizedToken, 0, 20).'...',
        ]);

        $response = $client
            ->asJson()
            ->send($methodUpper, $url, [
                'query' => $query,
                'json' => $payload,
            ]);

        Log::info('Google API Response', [
            'method' => $methodUpper,
            'url' => $url,
            'status' => $response->status(),
            'is_json' => (string) $response->header('content-type', ''),
        ]);

        $this->logGoogleApiResponse($methodUpper, $url, $response->status(), [
            'query' => $query,
            'request' => $payload,
            'response' => $this->responseBodyForLog($response),
        ]);

        try {
            if ($this->isHtmlResponse($response)) {
                throw new \RuntimeException('Google API returned HTML -> Authorization header missing or malformed request', $response->status() ?: 400);
            }

            $response->throw();
        } catch (RequestException $exception) {
            $body = $response->json();
            $status = $response->status() ?: 400;
            $message = 'Google API request failed.';
            $isHtmlResponse = $this->isHtmlResponse($response);

            if (is_array($body)) {
                $message = (string) data_get($body, 'error.message')
                    ?: (string) data_get($body, 'error_description')
                    ?: (string) data_get($body, 'message')
                    ?: $message;
            } else {
                $rawBody = trim($response->body());
                if ($rawBody !== '') {
                    $message = mb_substr($rawBody, 0, 350);
                }
            }

            if ($isHtmlResponse) {
                Log::error('Google API returned HTML response (malformed request or upstream mismatch).', [
                    'method' => strtoupper($method),
                    'url' => $url,
                    'status' => $status,
                    'query' => $query,
                    'request' => $payload,
                    'response_snippet' => mb_substr(trim((string) $response->body()), 0, 700),
                ]);
            }

            throw new \RuntimeException("Google API error ({$status}): {$message}", $status, $exception);
        }

        if ($response->status() === 204) {
            return [];
        }

        return $response->json() ?? [];
    }

    private function calendarBaseUrl(): string
    {
        return $this->resolveGoogleApiBaseUrl(
            (string) config('services.google.calendar_base_url'),
            'https://www.googleapis.com/calendar/v3'
        );
    }

    private function youtubeBaseUrl(): string
    {
        return $this->resolveGoogleApiBaseUrl(
            (string) config('services.google.youtube_base_url'),
            'https://www.googleapis.com/youtube/v3'
        );
    }

    private function driveBaseUrl(): string
    {
        return $this->resolveGoogleApiBaseUrl(
            (string) config('services.google.drive_base_url'),
            'https://www.googleapis.com/drive/v3'
        );
    }

    private function resolveGoogleApiBaseUrl(string $configuredUrl, string $fallbackUrl): string
    {
        $trimmed = rtrim(trim($configuredUrl), '/');
        if ($trimmed === '') {
            return $fallbackUrl;
        }

        $host = parse_url($trimmed, PHP_URL_HOST);
        if (! is_string($host) || ! str_contains(strtolower($host), 'googleapis.com')) {
            return $fallbackUrl;
        }

        return $trimmed;
    }

    private function responseBodyForLog(\Illuminate\Http\Client\Response $response): mixed
    {
        $json = $response->json();
        if (is_array($json)) {
            return $json;
        }

        $raw = trim($response->body());

        return $raw === '' ? null : mb_substr($raw, 0, 1500);
    }

    private function logGoogleApiResponse(string $method, string $url, int $status, array $context = []): void
    {
        $level = $status >= 400 ? 'error' : 'info';
        Log::log($level, 'Google API call completed', [
            'method' => $method,
            'url' => $url,
            'status' => $status,
            ...$context,
        ]);
    }

    private function normalizeAccessToken(string $token): string
    {
        $value = trim($token);
        if (str_starts_with(strtolower($value), 'bearer ')) {
            $value = trim(substr($value, 7));
        }

        // Remove line breaks/control chars that can break Authorization header formatting.
        return (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
    }

    private function assertValidAccessToken(string $token): void
    {
        if ($token === '') {
            throw new \RuntimeException('Google access token is missing. Connect Google account or refresh token first.', 422);
        }

        if (mb_strlen($token) < 20) {
            throw new \RuntimeException('Google access token appears invalid (too short).', 422);
        }
    }

    private function isHtmlResponse(\Illuminate\Http\Client\Response $response): bool
    {
        $contentType = strtolower((string) $response->header('Content-Type', ''));
        if (str_contains($contentType, 'text/html')) {
            return true;
        }

        $body = ltrim((string) $response->body());

        return str_starts_with(strtolower($body), '<!doctype html')
            || str_starts_with(strtolower($body), '<html');
    }

    private function googleClient(string $accessToken): PendingRequest
    {
        if ($accessToken === '') {
            throw new \RuntimeException('Google access token is missing', 422);
        }

        return $this->http
            ->withToken($accessToken)
            ->acceptJson()
            ->timeout(30);
    }
}
