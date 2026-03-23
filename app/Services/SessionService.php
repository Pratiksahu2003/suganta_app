<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use App\Support\CacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Jenssegers\Agent\Agent;

class SessionService
{
    private const GEOIP_CACHE_TTL_SECONDS = 86400;
    private const SESSION_STATS_CACHE_TTL_SECONDS = 120;

    protected Agent $agent;

    public function __construct()
    {
        $this->agent = new Agent();
    }

    /**
     * Create a new user session
     */
    public function createSession(User $user, Request $request): UserSession
    {
        $sessionId = session()->getId();
        
        // Check if session already exists for this session ID
        $existingSession = UserSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if ($existingSession) {
            // Update existing session
            $existingSession->update([
                'is_active' => true,
                'is_current_session' => true,
                'last_activity' => now(),
                'login_at' => now(),
            ]);
            $this->forgetSessionStatsCache($user->id);
            return $existingSession;
        }

        // Check if multiple sessions are allowed
        if (!config('session.allow_multiple_sessions', true)) {
            // Deactivate all previous sessions for this user
            $this->deactivateUserSessions($user);
        }

        // Get device information
        $deviceInfo = $this->getDeviceInfo($request);
        
        // Get location information
        $locationInfo = $this->getLocationInfo($request->ip());

        // Create new session
        $session = UserSession::create([
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'browser_version' => $deviceInfo['browser_version'],
            'platform' => $deviceInfo['platform'],
            'platform_version' => $deviceInfo['platform_version'],
            'device_name' => $deviceInfo['device_name'],
            'location' => $locationInfo['location'],
            'city' => $locationInfo['city'],
            'state' => $locationInfo['state'],
            'country' => $locationInfo['country'],
            'latitude' => $locationInfo['latitude'],
            'longitude' => $locationInfo['longitude'],
            'timezone' => $locationInfo['timezone'],
            'is_active' => true,
            'is_current_session' => true, // This session is current for this device
            'last_activity' => now(),
            'login_at' => now(),
            'device_fingerprint' => $this->generateDeviceFingerprint($request),
        ]);
        $this->forgetSessionStatsCache($user->id);

        return $session;
    }

    /**
     * Update session activity
     */
    public function updateActivity(UserSession $session): void
    {
        $session->update([
            'last_activity' => now(),
        ]);
        $this->forgetSessionStatsCache($session->user_id);
    }

    /**
     * Deactivate a session
     */
    public function deactivateSession(UserSession $session): void
    {
        $session->update([
            'is_active' => false,
            'is_current_session' => false,
            'logout_at' => now(),
        ]);
        $this->forgetSessionStatsCache($session->user_id);
    }

    /**
     * Deactivate all sessions for a user
     */
    public function deactivateUserSessions(User $user): void
    {
        UserSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'is_current_session' => false,
                'logout_at' => now(),
            ]);
        $this->forgetSessionStatsCache($user->id);
    }

    /**
     * Deactivate all sessions except the current one
     */
    public function deactivateOtherSessions(User $user, UserSession $currentSession): void
    {
        UserSession::where('user_id', $user->id)
            ->where('id', '!=', $currentSession->id)
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'is_current_session' => false,
                'logout_at' => now(),
            ]);
        $this->forgetSessionStatsCache($user->id);
    }

    /**
     * Get device information from request
     */
    protected function getDeviceInfo(Request $request): array
    {
        $userAgent = $request->userAgent();

        // Set user agent for Agent class
        $this->agent->setUserAgent($userAgent);

        $deviceType = 'desktop';
        if ($this->agent->isMobile()) {
            $deviceType = 'mobile';
        } elseif ($this->agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($this->agent->isDesktop()) {
            $deviceType = $this->agent->is('Windows') ? 'desktop' : 'laptop';
        }

        $browser = $this->agent->browser();
        $browserVersion = $this->agent->version($browser);
        $platform = $this->agent->platform();
        $platformVersion = $this->agent->version($platform);

        $deviceName = $this->getDeviceName($userAgent);

        return [
            'device_type' => $deviceType,
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'platform' => $platform,
            'platform_version' => $platformVersion,
            'device_name' => $deviceName,
        ];
    }

    /**
     * Get device name from user agent
     */
    protected function getDeviceName(string $userAgent): string
    {
        // Extract device name from user agent
        if (preg_match('/\(([^)]+)\)/', $userAgent, $matches)) {
            $deviceInfo = $matches[1];
            
            // Common device patterns
            $patterns = [
                '/iPhone/i' => 'iPhone',
                '/iPad/i' => 'iPad',
                '/Macintosh/i' => 'Mac',
                '/Windows NT/i' => 'Windows PC',
                '/Linux/i' => 'Linux PC',
                '/Android/i' => 'Android Device',
            ];

            foreach ($patterns as $pattern => $name) {
                if (preg_match($pattern, $deviceInfo)) {
                    return $name;
                }
            }
        }

        return 'Unknown Device';
    }

    /**
     * Get location information from IP address
     */
    protected function getLocationInfo(string $ipAddress): array
    {
        // Skip for localhost and private IPs
        if (in_array($ipAddress, ['127.0.0.1', '::1']) || 
            preg_match('/^192\.168\.|^10\.|^172\.(1[6-9]|2[0-9]|3[0-1])\./', $ipAddress)) {
            return [
                'location' => 'Local Network',
                'city' => 'Local',
                'state' => 'Local',
                'country' => 'Local',
                'latitude' => null,
                'longitude' => null,
                'timezone' => config('app.timezone'),
            ];
        }

        $cacheKey = "geoip:{$ipAddress}";

        return Cache::remember($cacheKey, now()->addSeconds(self::GEOIP_CACHE_TTL_SECONDS), function () use ($ipAddress) {
            try {
                // Reduced timeout to 2 seconds to prevent long waits on external dependency.
                $response = Http::timeout(2)->get("http://ip-api.com/json/{$ipAddress}");

                if ($response->successful()) {
                    $data = $response->json();

                    if (($data['status'] ?? 'fail') === 'success') {
                        return [
                            'location' => implode(', ', array_filter([
                                $data['city'] ?? null,
                                $data['regionName'] ?? null,
                                $data['country'] ?? null,
                            ])),
                            'city' => $data['city'] ?? null,
                            'state' => $data['regionName'] ?? null,
                            'country' => $data['country'] ?? null,
                            'latitude' => $data['lat'] ?? null,
                            'longitude' => $data['lon'] ?? null,
                            'timezone' => $data['timezone'] ?? config('app.timezone'),
                        ];
                    }
                }
            } catch (\Exception $e) {
                // Log error but don't fail
                // Only log in debug mode to avoid cluttering production logs with timeout errors
                if (config('app.debug')) {
                    Log::warning('Failed to get location for IP: ' . $ipAddress, ['error' => $e->getMessage()]);
                }
            }

            return [
                'location' => 'Unknown Location',
                'city' => null,
                'state' => null,
                'country' => null,
                'latitude' => null,
                'longitude' => null,
                'timezone' => config('app.timezone'),
            ];
        });
    }

    /**
     * Generate device fingerprint
     */
    protected function generateDeviceFingerprint(Request $request): string
    {
        $fingerprint = [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'accept_language' => $request->header('Accept-Language'),
            'accept_encoding' => $request->header('Accept-Encoding'),
            'screen_resolution' => $request->header('X-Screen-Resolution'),
            'color_depth' => $request->header('X-Color-Depth'),
            'timezone' => $request->header('X-Timezone'),
        ];

        return hash('sha256', json_encode($fingerprint));
    }

    /**
     * Get active sessions for a user with enhanced data
     */
    public function getActiveSessions(User $user): Collection
    {
        return UserSession::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(function ($session) {
                return $this->enhanceSessionData($session);
            });
    }

    /**
     * Get all sessions for a user with enhanced data
     */
    public function getAllSessions(User $user, int $limit = 50): Collection
    {
        return UserSession::where('user_id', $user->id)
            ->orderBy('login_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($session) {
                return $this->enhanceSessionData($session);
            });
    }

    /**
     * Get current session for a user with enhanced data
     */
    public function getCurrentSession(User $user): ?UserSession
    {
        $session = UserSession::where('user_id', $user->id)
            ->where('is_current_session', true)
            ->where('is_active', true)
            ->first();

        return $session ? $this->enhanceSessionData($session) : null;
    }

    /**
     * Enhance session data with additional computed attributes
     */
    protected function enhanceSessionData(UserSession $session): UserSession
    {
        // Add computed attributes
        $session->setAttribute('session_duration_formatted', $this->formatSessionDuration($session));
        $session->setAttribute('last_activity_formatted', $this->formatLastActivity($session));
        $session->setAttribute('login_time_formatted', $session->login_at->format('M j, Y g:i A'));
        $session->setAttribute('device_info_summary', $this->getDeviceInfoSummary($session));
        $session->setAttribute('location_summary', $this->getLocationSummary($session));
        $session->setAttribute('security_level', $this->getSecurityLevel($session));
        $session->setAttribute('activity_status', $this->getActivityStatus($session));
        $session->setAttribute('session_age', $this->getSessionAge($session));
        $session->setAttribute('is_suspicious', $this->isSuspiciousSession($session));

        return $session;
    }

    /**
     * Format session duration
     */
    protected function formatSessionDuration(UserSession $session): string
    {
        $start = $session->login_at;
        $end = $session->logout_at ?? now();
        $duration = $start->diff($end);

        if ($duration->days > 0) {
            return $duration->days . 'd ' . $duration->h . 'h ' . $duration->i . 'm';
        } elseif ($duration->h > 0) {
            return $duration->h . 'h ' . $duration->i . 'm';
        } else {
            return $duration->i . 'm';
        }
    }

    /**
     * Format last activity time
     */
    protected function formatLastActivity(UserSession $session): string
    {
        $lastActivity = $session->last_activity;
        $now = now();
        $diff = $lastActivity->diff($now);

        if ($diff->days > 0) {
            return $diff->days . ' days ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hours ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minutes ago';
        } else {
            return 'Just now';
        }
    }

    /**
     * Get device information summary
     */
    protected function getDeviceInfoSummary(UserSession $session): string
    {
        return $session->device_name . ' (' . ucfirst($session->device_type) . ')';
    }

    /**
     * Get location summary
     */
    protected function getLocationSummary(UserSession $session): string
    {
        $parts = [];
        if ($session->city) $parts[] = $session->city;
        if ($session->state) $parts[] = $session->state;
        if ($session->country) $parts[] = $session->country;

        return implode(', ', $parts) ?: 'Unknown location';
    }

    /**
     * Get security level for session
     */
    protected function getSecurityLevel(UserSession $session): string
    {
        // Check for suspicious indicators
        $indicators = [];
        
        if ($this->isSuspiciousSession($session)) {
            $indicators[] = 'suspicious';
        }
        
        if ($session->device_type === 'mobile' && !$session->is_current_session) {
            $indicators[] = 'mobile';
        }
        
        if ($session->last_activity->diffInMinutes(now()) > 60) {
            $indicators[] = 'inactive';
        }

        if (empty($indicators)) {
            return 'secure';
        }

        return implode(',', $indicators);
    }

    /**
     * Get activity status
     */
    protected function getActivityStatus(UserSession $session): string
    {
        $lastActivity = $session->last_activity;
        $now = now();
        $diffMinutes = $lastActivity->diffInMinutes($now);

        if ($diffMinutes < 5) {
            return 'active';
        } elseif ($diffMinutes < 30) {
            return 'recent';
        } elseif ($diffMinutes < 120) {
            return 'idle';
        } else {
            return 'inactive';
        }
    }

    /**
     * Get session age
     */
    protected function getSessionAge(UserSession $session): string
    {
        $start = $session->login_at;
        $now = now();
        $diff = $start->diff($now);

        if ($diff->days > 0) {
            return $diff->days . ' days old';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hours old';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minutes old';
        } else {
            return 'Just started';
        }
    }

    /**
     * Check if session is suspicious
     */
    protected function isSuspiciousSession(UserSession $session): bool
    {
        // Check for multiple sessions from same IP
        $sameIpSessions = UserSession::where('user_id', $session->user_id)
            ->where('ip_address', $session->ip_address)
            ->where('id', '!=', $session->id)
            ->where('is_active', true)
            ->count();

        // Check for unusual device fingerprint
        $unusualFingerprint = UserSession::where('user_id', $session->user_id)
            ->where('device_fingerprint', $session->device_fingerprint)
            ->where('id', '!=', $session->id)
            ->where('is_active', true)
            ->count();

        return $sameIpSessions > 2 || $unusualFingerprint > 1;
    }

    /**
     * Clean up old sessions
     */
    public function cleanupOldSessions(int $days = 30): int
    {
        $deleted = UserSession::where('created_at', '<', now()->subDays($days))
            ->where('is_active', false)
            ->delete();

        return $deleted;
    }

    /**
     * Get comprehensive session statistics
     */
    public function getSessionStats(User $user): array
    {
        $cacheKey = $this->sessionStatsCacheKey($user->id);

        return Cache::remember($cacheKey, now()->addSeconds(self::SESSION_STATS_CACHE_TTL_SECONDS), function () use ($user) {
            $totalSessions = UserSession::where('user_id', $user->id)->count();
            $activeSessions = UserSession::where('user_id', $user->id)
                ->where('is_active', true)
                ->count();
            $currentSession = $this->getCurrentSession($user);

            // Get device type breakdown
            $deviceStats = UserSession::where('user_id', $user->id)
                ->where('is_active', true)
                ->selectRaw('device_type, COUNT(*) as count')
                ->groupBy('device_type')
                ->get()
                ->pluck('count', 'device_type')
                ->toArray();

            // Get browser breakdown
            $browserStats = UserSession::where('user_id', $user->id)
                ->where('is_active', true)
                ->selectRaw('browser, COUNT(*) as count')
                ->groupBy('browser')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'browser')
                ->toArray();

            // Get location breakdown
            $locationStats = UserSession::where('user_id', $user->id)
                ->where('is_active', true)
                ->selectRaw('country, COUNT(*) as count')
                ->groupBy('country')
                ->orderBy('count', 'desc')
                ->get()
                ->pluck('count', 'country')
                ->toArray();

            // Get suspicious sessions count
            $suspiciousSessions = UserSession::where('user_id', $user->id)
                ->where('is_active', true)
                ->get()
                ->filter(function ($session) {
                    return $this->isSuspiciousSession($session);
                })
                ->count();

            return [
                'total_sessions' => $totalSessions,
                'active_sessions' => $activeSessions,
                'current_session' => $currentSession,
                'last_login' => UserSession::where('user_id', $user->id)
                    ->orderBy('login_at', 'desc')
                    ->first()?->login_at,
                'device_breakdown' => $deviceStats,
                'browser_breakdown' => $browserStats,
                'location_breakdown' => $locationStats,
                'suspicious_sessions' => $suspiciousSessions,
                'mobile_sessions' => $deviceStats['mobile'] ?? 0,
                'desktop_sessions' => $deviceStats['desktop'] ?? 0,
                'tablet_sessions' => $deviceStats['tablet'] ?? 0,
                'laptop_sessions' => $deviceStats['laptop'] ?? 0,
            ];
        });
    }

    protected function sessionStatsCacheKey(int $userId): string
    {
        $version = CacheVersion::get("session_stats_user:{$userId}");
        return "session:stats:user:v{$version}:{$userId}";
    }

    protected function forgetSessionStatsCache(int $userId): void
    {
        Cache::forget($this->sessionStatsCacheKey($userId));
    }
} 