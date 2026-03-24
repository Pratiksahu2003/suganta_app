<?php

namespace App\Services\V4\Google;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;

class GoogleTokenService
{
    public function __construct(private readonly HttpFactory $http) {}

    public function connect(
        User $user,
        string $refreshToken,
        ?string $accessToken = null,
        ?int $expiresIn = null,
        ?string $googleEmail = null,
        ?string $googleCalendarId = null
    ): User {
        $attributes = [
            'google_refresh_token' => $refreshToken,
        ];

        if ($accessToken) {
            $attributes['google_access_token'] = $accessToken;
        }

        if ($expiresIn) {
            $attributes['google_token_expires_at'] = now()->addSeconds(max(60, $expiresIn));
        }

        if ($googleEmail !== null) {
            $attributes['google_email'] = $googleEmail;
        }

        if ($googleCalendarId !== null) {
            $attributes['google_calendar_id'] = $googleCalendarId;
        }

        $user->forceFill($attributes)->save();

        return $user->refresh();
    }

    public function disconnect(User $user): User
    {
        $user->forceFill([
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'google_email' => null,
            'google_calendar_id' => null,
        ])->save();

        return $user->refresh();
    }

    public function getValidAccessToken(User $user, ?string $fallbackAccessToken = null): string
    {
        if ($fallbackAccessToken !== null && trim($fallbackAccessToken) !== '') {
            return $fallbackAccessToken;
        }

        $storedAccessToken = $user->google_access_token;
        $expiresAt = $user->google_token_expires_at;

        if ($storedAccessToken && $expiresAt && $expiresAt->gt(now()->addMinutes(2))) {
            return $storedAccessToken;
        }

        return $this->refreshAccessToken($user);
    }

    public function refreshAccessToken(User $user): string
    {
        if (! $user->google_refresh_token) {
            throw new RuntimeException('Google account is not connected. Please connect with refresh token first.', 422);
        }

        $oauth = $this->resolveOAuthClientConfig();
        $clientId = $oauth['client_id'];
        $clientSecret = $oauth['client_secret'];
        $tokenUrl = (string) config('services.google.oauth_token_url', 'https://oauth2.googleapis.com/token');

        if ($clientId === '' || $clientSecret === '') {
            throw new RuntimeException('Google OAuth credentials are not configured on server.', 500);
        }

        $response = $this->http
            ->asForm()
            ->acceptJson()
            ->timeout((int) config('services.google.timeout_seconds', 15))
            ->post($tokenUrl, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $user->google_refresh_token,
                'grant_type' => 'refresh_token',
            ]);

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'error_description', data_get($response->json(), 'error', 'Unable to refresh Google access token.'));
            throw new RuntimeException($message, $response->status() ?: 400);
        }

        $newAccessToken = (string) data_get($response->json(), 'access_token', '');
        $expiresIn = (int) data_get($response->json(), 'expires_in', 3600);

        if ($newAccessToken === '') {
            throw new RuntimeException('Google token refresh response did not include access token.', 500);
        }

        $expiry = CarbonImmutable::now()->addSeconds(max(60, $expiresIn));

        $user->forceFill([
            'google_access_token' => $newAccessToken,
            'google_token_expires_at' => $expiry,
        ])->save();

        return $newAccessToken;
    }

    public function exchangeAuthorizationCode(User $user, string $code, ?string $redirectUri = null): array
    {
        $oauth = $this->resolveOAuthClientConfig();
        $clientId = $oauth['client_id'];
        $clientSecret = $oauth['client_secret'];
        $tokenUrl = (string) config('services.google.oauth_token_url', 'https://oauth2.googleapis.com/token');
        $resolvedRedirectUri = $redirectUri ?: ((string) config('services.google.redirect_uri') ?: $oauth['redirect_uri']);

        if ($clientId === '' || $clientSecret === '' || $resolvedRedirectUri === '') {
            throw new RuntimeException('Google OAuth credentials/redirect URI are not configured on server.', 500);
        }

        $response = $this->http
            ->asForm()
            ->acceptJson()
            ->timeout((int) config('services.google.timeout_seconds', 15))
            ->post($tokenUrl, [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $resolvedRedirectUri,
                'grant_type' => 'authorization_code',
            ]);

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'error_description', data_get($response->json(), 'error', 'Unable to exchange Google authorization code.'));
            throw new RuntimeException($message, $response->status() ?: 400);
        }

        $accessToken = (string) data_get($response->json(), 'access_token', '');
        $refreshToken = (string) data_get($response->json(), 'refresh_token', '');
        $expiresIn = (int) data_get($response->json(), 'expires_in', 3600);

        if ($accessToken === '') {
            throw new RuntimeException('Google code exchange did not return access token.', 500);
        }

        $payload = [
            'google_access_token' => $accessToken,
            'google_token_expires_at' => CarbonImmutable::now()->addSeconds(max(60, $expiresIn)),
        ];

        if ($refreshToken !== '') {
            $payload['google_refresh_token'] = $refreshToken;
        }

        $user->forceFill($payload)->save();

        return [
            'access_token' => $accessToken,
            'refresh_token_received' => $refreshToken !== '',
            'expires_in' => $expiresIn,
            'status' => $this->status($user->refresh()),
        ];
    }

    public function buildAuthorizationUrl(User $user, ?string $redirectUri = null): array
    {
        $oauth = $this->resolveOAuthClientConfig();
        $clientId = $oauth['client_id'];
        $resolvedRedirectUri = $redirectUri ?: ((string) config('services.google.redirect_uri') ?: $oauth['redirect_uri']);
        $authorizeUrl = (string) config('services.google.oauth_authorize_url', 'https://accounts.google.com/o/oauth2/v2/auth');

        if ($clientId === '' || $resolvedRedirectUri === '') {
            throw new RuntimeException('Google OAuth client or redirect URI is not configured.', 500);
        }

        $state = Str::random(40);
        $this->rememberOauthState((int) $user->id, $state);
        $scopes = $this->defaultScopes();

        $query = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $resolvedRedirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);

        return [
            'oauth_url' => $authorizeUrl.'?'.$query,
            'state' => $state,
            'redirect_uri' => $resolvedRedirectUri,
            'scopes' => $scopes,
        ];
    }

    public function validateAndConsumeOauthState(User $user, ?string $state): void
    {
        if (! is_string($state) || trim($state) === '') {
            throw new RuntimeException('OAuth state is required.', 422);
        }

        $cacheKey = $this->oauthStateCacheKey((int) $user->id, $state);
        if (! Cache::pull($cacheKey)) {
            throw new RuntimeException('OAuth state is invalid or expired. Please restart OAuth flow.', 422);
        }
    }

    public function status(User $user): array
    {
        return [
            'connected' => ! empty($user->google_refresh_token),
            'google_email' => $user->google_email,
            'google_calendar_id' => $user->google_calendar_id,
            'token_expires_at' => optional($user->google_token_expires_at)?->toISOString(),
            'token_valid' => $user->google_token_expires_at?->gt(now()->addMinutes(2)) ?? false,
        ];
    }

    /**
     * Resolve OAuth client credentials from JSON first, env as fallback.
     *
     * @return array{client_id:string,client_secret:string,redirect_uri:string}
     */
    private function resolveOAuthClientConfig(): array
    {
        $clientId = (string) config('services.google.client_id', '');
        $clientSecret = (string) config('services.google.client_secret', '');
        $redirectUri = (string) config('services.google.redirect_uri', '');

        $jsonPath = (string) config('services.google.oauth_client_json', '');
        if ($jsonPath !== '') {
            $absolutePath = $this->resolvePath($jsonPath);
            if (File::exists($absolutePath)) {
                $decoded = json_decode((string) File::get($absolutePath), true);
                if (is_array($decoded)) {
                    $oauthNode = is_array($decoded['web'] ?? null)
                        ? $decoded['web']
                        : (is_array($decoded['installed'] ?? null) ? $decoded['installed'] : []);

                    $clientId = (string) ($oauthNode['client_id'] ?? $clientId);
                    $clientSecret = (string) ($oauthNode['client_secret'] ?? $clientSecret);

                    $redirectUris = $oauthNode['redirect_uris'] ?? [];
                    if ($redirectUri === '' && is_array($redirectUris) && isset($redirectUris[0])) {
                        $redirectUri = (string) $redirectUris[0];
                    }
                }
            }
        }

        return [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
        ];
    }

    private function resolvePath(string $path): string
    {
        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:\\\\/', $path) === 1) {
            return $path;
        }

        return base_path($path);
    }

    private function rememberOauthState(int $userId, string $state): void
    {
        $ttl = max(60, (int) config('services.google.oauth_state_ttl_seconds', 600));
        Cache::put($this->oauthStateCacheKey($userId, $state), true, now()->addSeconds($ttl));
    }

    private function oauthStateCacheKey(int $userId, string $state): string
    {
        return 'google:oauth:state:'.$userId.':'.$state;
    }

    /**
     * @return array<int, string>
     */
    private function defaultScopes(): array
    {
        $configured = config('services.google.default_scopes', []);
        if (is_array($configured) && $configured !== []) {
            return array_values(array_filter(array_map('strval', $configured)));
        }

        return [
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/drive',
            'https://www.googleapis.com/auth/youtube.readonly',
            'https://www.googleapis.com/auth/userinfo.email',
        ];
    }
}
