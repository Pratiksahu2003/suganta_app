<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail, CanResetPassword
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'address',
        'city',
        'state',
        'pincode',
        'country',
        'latitude',
        'longitude',
        'is_active',
        'verification_status',
        'registration_fee_status',
        'preferences',
        'push_subscription',
        'profile_image',
        'last_login_at',
        'google_calendar_id',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',
        'google_email',
        'referred_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'preferences',
        'push_subscription',
        'latitude',
        'longitude',
        'google_access_token',
        'google_refresh_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'preferences' => 'array',
            'push_subscription' => 'array',
            'is_active' => 'boolean',
            'verification_status' => 'string',
            'registration_fee_status' => 'string',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
            'google_token_expires_at' => 'datetime',
        ];
    }

    /**
     * Get the teacher profile associated with the user
     */
    public function teacherProfile()
    {
        return $this->hasOneThrough(
            ProfileTeachingInfo::class,
            Profile::class,
            'user_id', // Foreign key on profiles table
            'profile_id', // Foreign key on profile_teaching_info table
            'id', // Local key on users table
            'id' // Local key on profiles table
        );
    }

    /**
     * Get the teacher profile (ProfileTeachingInfo model) associated with the user
     */
    public function teacherProfileModel()
    {
        return $this->hasOneThrough(
            ProfileTeachingInfo::class,
            Profile::class,
            'user_id', // Foreign key on profiles table
            'profile_id', // Foreign key on profile_teaching_info table
            'id', // Local key on users table
            'id' // Local key on profiles table
        );
    }

    /**
     * Get the institute profile associated with the user (through Profile)
     */
    public function instituteProfile()
    {
        return $this->hasOne(Profile::class)->whereHas('user', function($query) {
            $query->whereIn('role', ['institute', 'university']);
        });
    }

    /**
     * Get the institute associated with the user.
     */
    public function institute()
    {
        return $this->hasOne(Institute::class);
    }

    /**
     * Get the student profile associated with the user
     */
    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    /**
     * Get the comprehensive profile associated with the user
     */
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    /**
     * Get the user's favorites
     */
    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Get the user's bookings (if student)
     */
    public function bookings()
    {
        return $this->hasManyThrough(Booking::class, StudentProfile::class, 'user_id', 'student_id');
    }

    /**
     * Get the user's notifications
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get the roles for the user
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')->withTimestamps();
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles()->where('name', $roleName)->exists();
    }

    /**
     * Check if user is a student
     */
    public function isStudent(): bool
    {
        return $this->role === 'student' || $this->hasRole('student');
    }

    /**
     * Check if user is a teacher
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher' || $this->hasRole('teacher');
    }

    /**
     * Check if user is an institute
     */
    public function isInstitute(): bool
    {
        return $this->role === 'institute' ||  $this->role === 'university';
    }


    /**
     * Check if user is an admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin' || $this->hasRole('admin') || $this->hasRole('super-admin');
    }

    /**
     * Check if user is verified
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if registration payment is required for this user's role
     */
    public function requiresRegistrationPayment(): bool
    {
        $registrationConfig = config('registration', []);
        $requiredRoles = data_get($registrationConfig, 'payment.required_for_roles', []);

        if (!is_array($requiredRoles)) {
            $requiredRoles = [];
        }

        return in_array($this->role, $requiredRoles, true);
    }

    /**
     * Check if registration payment is pending
     */
    public function hasPendingRegistrationPayment(): bool
    {
        if (!$this->requiresRegistrationPayment()) {
            return false;
        }

        return ($this->registration_fee_status ?? null) === 'pending';
    }

    /**
     * Check if email verification is pending
     */
    public function hasPendingEmailVerification(): bool
    {
        return !$this->hasVerifiedEmail();
    }

    /**
     * Get registration charges for this user's role
     */
    public function getRegistrationCharges(): ?array
    {
        $registrationConfig = config('registration', []);
        $charges = data_get($registrationConfig, 'charges', []);

        if (!is_array($charges)) {
            return null;
        }

        $role = $this->role;
        return is_string($role) ? ($charges[$role] ?? null) : null;
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Use the PermissionHelper for consistent permission checking
        return \App\Helpers\PermissionHelper::hasPermission($this, $permission);
    }


    /**
     * Assign a role to the user
     */
    public function assignRole(Role $role): bool
    {
        if (!$this->hasRole($role->name)) {
            $this->roles()->attach($role->id);
            return true;
        }
        return false;
    }

    /**
     * Remove a role from the user
     */
    public function removeRole(Role $role): bool
    {
        if ($this->hasRole($role->name)) {
            $this->roles()->detach($role->id);
            return true;
        }
        return false;
    }

    /**
     * Sync user roles
     */
    public function syncRoles(array $roleIds): void
    {
        $this->roles()->sync($roleIds);
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new \App\Notifications\ResetPasswordNotification($token));
    }

    /**
     * Get user's primary role for display
     */
    public function getPrimaryRoleAttribute(): string
    {
        return ucfirst($this->role);
    }

    /**
     * Get all sessions for the user
     */
    public function sessions()
    {
        return $this->hasMany(\App\Models\UserSession::class);
    }

    /**
     * Get active sessions for the user
     */
    public function activeSessions()
    {
        return $this->hasMany(\App\Models\UserSession::class)->where('is_active', true);
    }

    /**
     * Get current session for the user
     */
    public function currentSession()
    {
        return $this->hasOne(\App\Models\UserSession::class)->where('is_current_session', true);
    }

    /**
     * Get the login history for the user
     */
    public function loginHistory()
    {
        return $this->hasMany(UserSession::class)->orderBy('login_at', 'desc');
    }

    /**
     * Get the devices for the user
     */
    public function devices()
    {
        return $this->hasMany(UserSession::class)
            ->select('user_agent', 'device_type', 'browser', 'platform')
            ->distinct();
    }

    /**
     * Get user's avatar URL.
     * Uses configured upload disk (GCS returns direct storage.googleapis.com URL).
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile_image && !empty($this->profile_image)) {
            return storage_file_url($this->profile_image);
        }

        return 'data:image/svg+xml;base64,' . base64_encode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%239CA3AF" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>');
    }

    /**
     * Get user's profile photo URL (for compatibility with social logins)
     */
    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->avatar_url;
    }

    /**
     * Get the user's profile image URL.
     * Uses configured upload disk (GCS returns direct storage.googleapis.com URL).
     */
    public function getProfileImageUrlAttribute()
    {
        if ($this->profile && $this->profile->profile_image) {
            $timestamp = time();
            return storage_file_url($this->profile->profile_image) . '?v=' . $timestamp;
        }
        return asset('images/default-avatar.png');
    }

    /**
     * Get the user's profile image path
     */
    public function getProfileImageAttribute()
    {
        if ($this->profile && $this->profile->profile_image) {
            return $this->profile->profile_image;
        }
        return null;
    }

    /**
     * Scope to filter active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by role
     */
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\EmailVerificationNotification);
    }

    /**
     * Get online status (simplified - you can enhance this)
     */
    public function isOnline(): bool
    {
        // Check if user has active sessions in the last 5 minutes
        return $this->activeSessions()
            ->where('last_activity', '>=', now()->subMinutes(5))
            ->exists();
    }

    // ===== REVIEW RELATIONSHIPS =====

    /**
     * Get reviews written by this user
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Get reviews received by this user (for teachers/institutes)
     */
    public function receivedReviews()
    {
        return Review::whereHasMorph('reviewable', [TeacherProfile::class, Institute::class], function ($query) {
            $query->where('user_id', $this->id);
        });
    }

    /**
     * Get reviews for this user's teacher profile
     */
    public function teacherReviews()
    {
        return $this->hasManyThrough(Review::class, TeacherProfile::class, 'user_id', 'reviewable_id')
            ->where('reviews.reviewable_type', TeacherProfile::class);
    }

    /**
     * Get reviews for this user's institute
     */
    public function instituteReviews()
    {
        return $this->hasManyThrough(Review::class, Institute::class, 'user_id', 'reviewable_id')
            ->where('reviews.reviewable_type', Institute::class);
    }

    /**
     * Get average rating received by this user
     */
    public function getAverageRatingAttribute()
    {
        $teacherReviews = $this->teacherReviews()->where('status', 'published');
        $instituteReviews = $this->instituteReviews()->where('status', 'published');
        
        $totalReviews = $teacherReviews->count() + $instituteReviews->count();
        if ($totalReviews === 0) {
            return 0;
        }
        
        $totalRating = $teacherReviews->sum('rating') + $instituteReviews->sum('rating');
        return round($totalRating / $totalReviews, 1);
    }

    /**
     * Get total reviews count received by this user
     */
    public function getTotalReviewsCountAttribute()
    {
        return $this->teacherReviews()->where('status', 'published')->count() + 
               $this->instituteReviews()->where('status', 'published')->count();
    }

    /**
     * Check if user can review another user
     */
    public function canReview($reviewable): bool
    {
        // Users can't review themselves
        if ($this->id === $reviewable->user_id) {
            return false;
        }

        // Check if user has already reviewed this entity
        return !Review::hasUserReviewed($this, $reviewable);
    }

    /**
     * Get leads where this user is the lead owner (teacher)
     */
    public function leadsAsOwner()
    {
        return $this->hasMany(Lead::class, 'lead_owner_id');
    }

    /**
     * Get leads assigned to this user
     */
    public function assignedLeads()
    {
        return $this->hasMany(Lead::class, 'assigned_to');
    }

    /**
     * Get leads created by this user
     */
    public function createdLeads()
    {
        return $this->hasMany(Lead::class, 'user_id');
    }

    /**
     * Get portfolios for this user (deprecated - use portfolio() instead)
     * @deprecated Use portfolio() instead for single portfolio relationship
     */
    public function portfolios()
    {
        return $this->hasMany(Portfolio::class);
    }

    /**
     * Get the single portfolio for this user
     */
    public function portfolio()
    {
        return $this->hasOne(Portfolio::class);
    }

    /**
     * Get subscriptions for this user
     */
    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    /**
     * Get active subscription for this user
     */
    public function activeSubscription()
    {
        return $this->hasOne(UserSubscription::class)
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest();
    }

    /**
     * Get active subscription for a specific type
     */
    public function activeSubscriptionForType(int $sType)
    {
        return $this->hasOne(UserSubscription::class)
            ->whereHas('plan', function($q) use ($sType) {
                $q->where('s_type', $sType);
            })
            ->where('status', 'active')
            ->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->latest();
    }

    /**
     * Check if user has active subscription
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Check if user has active subscription for a specific type
     */
    public function hasActiveSubscriptionForType(int $sType): bool
    {
        return $this->activeSubscriptionForType($sType)->exists();
    }

    /**
     * Get all payments for this user
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Get note purchases for this user
     */
    public function notePurchases()
    {
        return $this->hasMany(NotePurchase::class);
    }

    /**
     * Get portfolio upload limits based on subscription (s_type = 1 = Portfolio plans).
     * If no active subscription: uses free plan (s_type=1, price=0) from subscription_plans.
     */
    public function getPortfolioLimits(): array
    {
        $activeSubscription = $this->activeSubscriptionForType(1)->with('plan')->first();

        if ($activeSubscription && $activeSubscription->plan) {
            return [
                'max_images' => $activeSubscription->plan->max_images,
                'max_files' => $activeSubscription->plan->max_files,
            ];
        }

        // Use free plan (s_type=1, price=0) from subscription_plans
        $freePlan = SubscriptionPlan::active()
            ->where('s_type', 1)
            ->where('price', 0)
            ->first();

        if ($freePlan) {
            return [
                'max_images' => $freePlan->max_images,
                'max_files' => $freePlan->max_files,
            ];
        }

        // Fallback if no free plan exists in DB
        return [
            'max_images' => 2,
            'max_files' => 2,
        ];
    }

    /**
     * Get the wallet associated with the user
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Get or create wallet for this user
     */
    public function getWallet()
    {
        return Wallet::getOrCreate($this->id);
    }

    /**
     * Get the user who referred this user.
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by', 'phone');
    }

    /**
     * Get the users referred by this user.
     */
    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by', 'phone');
    }
}
