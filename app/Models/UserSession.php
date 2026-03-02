<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    use HasFactory;

    /**
     * Only real DB columns should be listed here. Never add computed or display-only fields!
     */
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'browser_version',
        'platform',
        'platform_version',
        'device_name',
        'location',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'timezone',
        'is_active',
        'last_activity',
        'login_at',
        'logout_at',
        'device_fingerprint',
        'is_current_session',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_current_session' => 'boolean',
        'last_activity' => 'datetime',
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Get the user that owns the session
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get active sessions
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get current session
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_current_session', true);
    }

    /**
     * Get device icon based on device type
     */
    public function getDeviceIconAttribute(): string
    {
        return match ($this->device_type) {
            'mobile' => 'bi bi-phone',
            'tablet' => 'bi bi-tablet',
            'desktop' => 'bi bi-desktop',
            'laptop' => 'bi bi-laptop',
            default => 'bi bi-desktop',
        };
    }

    /**
     * Get browser icon
     */
    public function getBrowserIconAttribute(): string
    {
        $browser = strtolower($this->browser);
        
        return match (true) {
            str_contains($browser, 'chrome') => 'bi bi-chrome',
            str_contains($browser, 'firefox') => 'bi bi-firefox',
            str_contains($browser, 'safari') => 'bi bi-safari',
            str_contains($browser, 'edge') => 'bi bi-edge',
            str_contains($browser, 'opera') => 'bi bi-opera',
            default => 'bi bi-globe',
        };
    }

    /**
     * Get enhanced status badge with activity status
     */
    public function getEnhancedStatusBadgeAttribute(): string
    {
        $activityStatus = $this->activity_status ?? 'unknown';
        $isSuspicious = $this->is_suspicious ?? false;
        
        $badgeClass = match ($activityStatus) {
            'active' => 'bg-success',
            'recent' => 'bg-info',
            'idle' => 'bg-warning',
            'inactive' => 'bg-secondary',
            default => 'bg-secondary',
        };

        $icon = match ($activityStatus) {
            'active' => 'bi-circle-fill',
            'recent' => 'bi-clock',
            'idle' => 'bi-hourglass-split',
            'inactive' => 'bi-circle',
            default => 'bi-question-circle',
        };

        $text = match ($activityStatus) {
            'active' => 'Active',
            'recent' => 'Recent',
            'idle' => 'Idle',
            'inactive' => 'Inactive',
            default => 'Unknown',
        };

        $badge = "<span class='badge {$badgeClass} me-2'><i class='bi {$icon}'></i> {$text}</span>";
        
        if ($isSuspicious) {
            $badge .= "<span class='badge bg-danger'><i class='bi bi-exclamation-triangle'></i> Suspicious</span>";
        }

        return $badge;
    }

    /**
     * Get security level badge
     */
    public function getSecurityLevelBadgeAttribute(): string
    {
        $securityLevel = $this->security_level ?? 'secure';
        $levels = explode(',', $securityLevel);
        
        $badges = [];
        foreach ($levels as $level) {
            $level = trim($level);
            $badgeClass = match ($level) {
                'secure' => 'bg-success',
                'suspicious' => 'bg-danger',
                'mobile' => 'bg-info',
                'inactive' => 'bg-warning',
                default => 'bg-secondary',
            };

            $icon = match ($level) {
                'secure' => 'bi-shield-check',
                'suspicious' => 'bi-exclamation-triangle',
                'mobile' => 'bi-phone',
                'inactive' => 'bi-clock',
                default => 'bi-question-circle',
            };

            $text = ucfirst($level);
            $badges[] = "<span class='badge {$badgeClass}'><i class='bi {$icon}'></i> {$text}</span>";
        }

        return implode(' ', $badges);
    }

    /**
     * Get security level text
     */
    public function getSecurityLevelAttribute(): string
    {
        return $this->security_level ?? 'secure';
    }

    /**
     * Get activity status
     */
    public function getActivityStatusAttribute(): string
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if (!$this->last_activity) {
            return 'unknown';
        }

        $minutesSinceLastActivity = $this->last_activity->diffInMinutes(now());

        return match (true) {
            $minutesSinceLastActivity <= 5 => 'active',
            $minutesSinceLastActivity <= 30 => 'recent',
            $minutesSinceLastActivity <= 60 => 'idle',
            default => 'inactive',
        };
    }

    /**
     * Get session age
     */
    public function getSessionAgeAttribute(): string
    {
        if (!$this->login_at) {
            return 'Unknown';
        }

        $age = $this->login_at->diffForHumans();
        return $age;
    }

    /**
     * Get login time formatted
     */
    public function getLoginTimeFormattedAttribute(): string
    {
        if (!$this->login_at) {
            return 'Unknown';
        }

        return $this->login_at->format('M j, Y g:i A');
    }

    /**
     * Get device info summary
     */
    public function getDeviceInfoSummaryAttribute(): string
    {
        $parts = [];
        
        if ($this->browser && $this->browser_version) {
            $parts[] = "{$this->browser} {$this->browser_version}";
        }
        
        if ($this->platform && $this->platform_version) {
            $parts[] = "on {$this->platform} {$this->platform_version}";
        }
        
        return implode(' ', $parts) ?: 'Unknown device';
    }

    /**
     * Get location summary
     */
    public function getLocationSummaryAttribute(): string
    {
        $parts = [];
        
        if ($this->city) {
            $parts[] = $this->city;
        }
        
        if ($this->state) {
            $parts[] = $this->state;
        }
        
        if ($this->country) {
            $parts[] = $this->country;
        }
        
        return implode(', ', $parts) ?: 'Unknown location';
    }

    /**
     * Check if session is suspicious
     */
    public function getIsSuspiciousAttribute(): bool
    {
        // Basic suspicious activity detection
        $suspicious = false;
        
        // Check if login time is unusual (between 11 PM and 5 AM)
        if ($this->login_at) {
            $hour = $this->login_at->hour;
            if ($hour >= 23 || $hour <= 5) {
                $suspicious = true;
            }
        }
        
        // Check if multiple sessions from same IP
        $sameIpSessions = static::where('user_id', $this->user_id)
            ->where('ip_address', $this->ip_address)
            ->where('id', '!=', $this->id)
            ->where('is_active', true)
            ->count();
            
        if ($sameIpSessions > 2) {
            $suspicious = true;
        }
        
        return $suspicious;
    }

    /**
     * Get device type badge
     */
    public function getDeviceTypeBadgeAttribute(): string
    {
        $deviceType = ucfirst($this->device_type);
        $badgeClass = match ($this->device_type) {
            'mobile' => 'bg-primary',
            'tablet' => 'bg-info',
            'desktop' => 'bg-success',
            'laptop' => 'bg-warning',
            default => 'bg-secondary',
        };

        return "<span class='badge {$badgeClass}'>{$deviceType}</span>";
    }

    /**
     * Get session duration in human readable format
     */
    public function getDurationFormattedAttribute(): string
    {
        $start = $this->login_at;
        $end = $this->logout_at ?? now();
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
     * Get last activity in human readable format
     */
    public function getLastActivityFormattedAttribute(): string
    {
        $lastActivity = $this->last_activity;
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
     * Get platform icon
     */
    public function getPlatformIconAttribute(): string
    {
        $platform = strtolower($this->platform);
        
        return match (true) {
            str_contains($platform, 'windows') => 'fab fa-windows',
            str_contains($platform, 'mac') => 'fab fa-apple',
            str_contains($platform, 'linux') => 'fab fa-linux',
            str_contains($platform, 'android') => 'fab fa-android',
            str_contains($platform, 'ios') => 'fab fa-app-store-ios',
            default => 'fas fa-desktop',
        };
    }

    /**
     * Get session duration
     */
    public function getSessionDurationAttribute(): string
    {
        if (!$this->login_at) {
            return 'Unknown';
        }

        $endTime = $this->logout_at ?? now();
        $duration = $this->login_at->diff($endTime);

        if ($duration->days > 0) {
            return $duration->days . 'd ' . $duration->h . 'h ' . $duration->i . 'm';
        } elseif ($duration->h > 0) {
            return $duration->h . 'h ' . $duration->i . 'm';
        } else {
            return $duration->i . 'm';
        }
    }

    /**
     * Get location display
     */
    public function getLocationDisplayAttribute(): string
    {
        $parts = array_filter([$this->city, $this->state, $this->country]);
        return implode(', ', $parts) ?: 'Unknown Location';
    }

    /**
     * Check if session is current
     */
    public function isCurrentSession(): bool
    {
        return $this->is_current_session;
    }

    /**
     * Check if session is active
     */
    public function isActive(): bool
    {
        return $this->is_active && !$this->logout_at;
    }

    /**
     * Get status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        if ($this->isCurrentSession()) {
            return '<span class="badge bg-success">Current Session</span>';
        } elseif ($this->isActive()) {
            return '<span class="badge bg-primary">Active</span>';
        } else {
            return '<span class="badge bg-secondary">Inactive</span>';
        }
    }
} 