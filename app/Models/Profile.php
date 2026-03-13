<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class Profile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'display_name',
        'bio',
        'date_of_birth',
        'gender_id',
        'nationality',
        'religion',
        'marital_status',
        'phone_primary',
        'phone_secondary',
        'whatsapp',
        'skype',
        'website',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relation',
        'address_line_1',
        'address_line_2',
        'area',
        'city',
        'state',
        'pincode',
        'country_id',
        'latitude',
        'longitude',
        'timezone_id',
        'location_auto_detected',
        'location_last_updated',
        'facebook_url',
        'twitter_url',
        'instagram_url',
        'linkedin_url',
        'youtube_url',
        'tiktok_url',
        'telegram_username',
        'discord_username',
        'github_url',
        'portfolio_url',
        'blog_url',
        'profession',
        'company_name',
        'job_title',
        'department',
        'work_experience',
        'skills',
        'certifications',
        'awards',
        'publications',
        'hourly_rate',
        'monthly_rate',
        'teaching_mode',
        'online_classes',
        'home_tuition',
        'institute_classes',
        'travel_radius_km',
        'subjects_taught',
        'grade_levels_taught',
        'exam_preparation',
        'teaching_philosophy',
        'teaching_methods',
        'available_timings',
        'availability_status',
        'institute_name',
        'institute_type',
        'institute_category',
        'affiliation_number',
        'registration_number',
        'udise_code',
        'aicte_code',
        'ugc_code',
        'establishment_year',
        'principal_name',
        'principal_phone',
        'principal_email',
        'total_students',
        'total_teachers',
        'total_branches',
        'facilities',
        'accreditations',
        'affiliations',
        'institute_description',
        'courses_offered',
        'specializations',
        'is_main_branch',
        'parent_institute_id',
        'student_id',
        'current_class',
        'current_school',
        'board',
        'stream',
        'subjects_of_interest',
        'learning_goals',
        'learning_mode',
        'budget_min',
        'budget_max',
        'preferred_timing',
        'learning_challenges',
        'special_requirements',
        'extracurricular_interests',
        'parent_name',
        'parent_phone',
        'parent_email',
        'guardian_name',
        'guardian_phone',
        'guardian_email',
        'current_grades',
        'target_grades',
        'previous_tutoring_experience',
        'is_verified',
        'verified_at',
        'verified_by',
        'is_featured',
        'is_active',
        'profile_completion_status',
        'profile_completion_percentage',
        'preferences',
        'notification_settings',
        'privacy_settings',
        'language',
        'date_format',
        'time_format',
        'profile_image',
        'cover_image',
        'gallery_images',
        'documents',
        'certificates',
        'profile_views',
        'profile_likes',
        'profile_shares',
        'last_activity_at',
        'last_login_at',
        'activity_log',
        'slug',
        'meta_description',
        'meta_keywords',
        'seo_title',
        'highest_qualification',
        'institution_name',
        'field_of_study',
        'graduation_year',
        'cgpa',
        'languages_spoken',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'graduation_year' => 'integer',
        'establishment_year' => 'integer',
        'cgpa' => 'decimal:2',
        'languages_spoken' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'location_auto_detected' => 'boolean',
        'location_last_updated' => 'datetime',
        'skills' => 'array',
        'certifications' => 'array',
        'awards' => 'array',
        'publications' => 'array',
        'research_interests' => 'array',
        'educational_background' => 'array',
        'languages_spoken' => 'array',
        'hourly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'online_classes' => 'boolean',
        'home_tuition' => 'boolean',
        'institute_classes' => 'boolean',
        'travel_radius_km' => 'integer',
        'subjects_taught' => 'array',
        'grade_levels_taught' => 'array',
        'exam_preparation' => 'array',
        'teaching_methods' => 'array',
        'available_timings' => 'array',
        'facilities' => 'array',
        'accreditations' => 'array',
        'affiliations' => 'array',
        'courses_offered' => 'array',
        'specializations' => 'array',
        'is_main_branch' => 'boolean',
        'subjects_of_interest' => 'array',
        'learning_goals' => 'array',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'extracurricular_interests' => 'array',
        'current_grades' => 'array',
        'target_grades' => 'array',
        'previous_tutoring_experience' => 'boolean',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'verified_at' => 'datetime',
        'preferences' => 'array',
        'notification_settings' => 'array',
        'privacy_settings' => 'array',
        'gallery_images' => 'array',
        'documents' => 'array',
        'certificates' => 'array',
        'profile_views' => 'integer',
        'profile_likes' => 'integer',
        'profile_shares' => 'integer',
        'last_activity_at' => 'datetime',
        'last_login_at' => 'datetime',
        'activity_log' => 'array',
        'meta_keywords' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($profile) {
            if (empty($profile->slug)) {
            $baseSlug = Str::slug($profile->display_name ?? $profile->first_name . ' ' . $profile->last_name);
            $profile->slug = $baseSlug . '-' . $profile->user_id;
            }
        });

        static::updating(function ($profile) {
            if ($profile->isDirty('display_name') || $profile->isDirty('first_name') || $profile->isDirty('last_name')) {
            $baseSlug = Str::slug($profile->display_name ?? $profile->first_name . ' ' . $profile->last_name);
            $profile->slug = $baseSlug . '-' . $profile->user_id;
            }
        });
    }

    /**
     * Get the user that owns the profile
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent institute (for branches)
     */
    public function parentInstitute()
    {
        return $this->belongsTo(Profile::class, 'parent_institute_id');
    }

    /**
     * Get the branch institutes
     */
    public function branches()
    {
        return $this->hasMany(Profile::class, 'parent_institute_id');
    }

    /**
     * Get the social media links
     */
    public function socialLinks()
    {
        return $this->hasMany(ProfileSocialLink::class);
    }

    /**
     * Get subjects taught with names (for subjects_taught array)
     */
    public function getSubjectsTaughtWithNamesAttribute()
    {
        if (!$this->subjects_taught || empty($this->subjects_taught)) {
            return [];
        }

        $subjectIds = is_array($this->subjects_taught) ? $this->subjects_taught : json_decode($this->subjects_taught, true);
        
        if (empty($subjectIds)) {
            return [];
        }

        return Subject::whereIn('id', $subjectIds)
                    ->where('is_active', true)
                    ->select('id', 'name', 'slug', 'category', 'level')
                    ->orderBy('name')
                    ->get()
                    ->toArray();
    }

    /**
     * Get subjects taught names only
     */
    public function getSubjectsTaughtNamesAttribute()
    {
        $subjects = $this->subjects_taught_with_names;
        return array_column($subjects, 'name');
    }

    /**
     * Get the institute information
     */
    public function instituteInfo()
    {
        return $this->hasOne(ProfileInstituteInfo::class);
    }

    /**
     * Get or create institute information
     */
    public function getOrCreateInstituteInfo()
    {
        $info = $this->instituteInfo;
        if (!$info) {
            $info = ProfileInstituteInfo::create([
                'profile_id' => $this->id
            ]);
        }
        return $info;
    }

    /**
     * Get the student information
     */
    public function studentInfo()
    {
        return $this->hasOne(ProfileStudentInfo::class);
    }

    /**
     * Get or create student information
     */
    public function getOrCreateStudentInfo()
    {
        $info = $this->studentInfo;
        if (!$info) {
            $info = ProfileStudentInfo::create([
                'profile_id' => $this->id
            ]);
        }
        return $info;
    }

    /**
     * Get the teaching information
     */
    public function teachingInfo()
    {
        return $this->hasOne(ProfileTeachingInfo::class);
    }

    /**
     * Get or create teaching information
     */
    public function getOrCreateTeachingInfo()
    {
        $info = $this->teachingInfo;
        if (!$info) {
            $info = ProfileTeachingInfo::create([
                'profile_id' => $this->id
            ]);
        }
        return $info;
    }

    /**
     * Get the professional information
     */
    public function professionalInfo()
    {
        return $this->hasOne(ProfileProfessionalInfo::class);
    }

    /**
     * Get or create professional information
     */
    public function getOrCreateProfessionalInfo()
    {
        $info = $this->professionalInfo;
        if (!$info) {
            $info = ProfileProfessionalInfo::create([
                'profile_id' => $this->id
            ]);
        }
        return $info;
    }



    /**
     * Get the profile media
     */
    public function media()
    {
        return $this->hasMany(ProfileMedia::class);
    }

    /**
     * Get the full name
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the display name or full name
     */
    public function getDisplayNameAttribute($value)
    {
        return $value ?: $this->full_name;
    }

    /**
     * Get the age
     */
    public function getAgeAttribute()
    {
        return $this->date_of_birth ? $this->date_of_birth->age : null;
    }

    /**
     * Get gender label from integer value
     */
    public function getGenderLabelAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('gender', $this->gender_id);
    }

    /**
     * Get country label from integer value
     */
    public function getCountryLabelAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('country', $this->country_id);
    }

    /**
     * Get timezone label from integer value
     */
    public function getTimezoneLabelAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('timezone', $this->timezone_id);
    }

    /**
     * Get the complete address
     */
    public function getCompleteAddressAttribute()
    {
        $parts = array_filter([
            $this->address_line_1,
            $this->address_line_2,
            $this->area,
            $this->city,
            $this->state,
            $this->pincode,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Get social media links
     */
    public function getSocialMediaLinksAttribute()
    {
        return array_filter([
            'facebook' => $this->facebook_url,
            'twitter' => $this->twitter_url,
            'instagram' => $this->instagram_url,
            'linkedin' => $this->linkedin_url,
            'youtube' => $this->youtube_url,
            'tiktok' => $this->tiktok_url,
            'telegram' => $this->telegram_username,
            'discord' => $this->discord_username,
            'github' => $this->github_url,
            'portfolio' => $this->portfolio_url,
            'blog' => $this->blog_url,
        ]);
    }

    /**
     * Check if profile is for a teacher
     */
    public function isTeacher()
    {
        return $this->user->role === 'teacher';
    }

    /**
     * Check if profile is for a student
     */
    public function isStudent()
    {
        return $this->user->role === 'student';
    }

    /**
     * Check if profile is for an institute
     */
    public function isInstitute()
    {
        return in_array($this->user->role, ['institute', 'university', 'ngo']);
    }

    /**
     * Get profile image URL.
     * Uses configured upload disk (GCS returns direct storage.googleapis.com URL).
     */
    public function getProfileImageUrlAttribute()
    {
        if ($this->profile_image && !empty(trim($this->profile_image))) {
            $disk = config('filesystems.upload_disk', 'public');
            if (Storage::disk($disk)->exists($this->profile_image)) {
                $timestamp = time();
                return storage_file_url($this->profile_image) . '?v=' . $timestamp;
            }
        }

        return $this->getDefaultProfileImageUrl();
    }

    /**
     * Get default profile image URL based on user role
     */
    public function getDefaultProfileImageUrl()
    {
        $user = $this->user;
        
        if (!$user) {
            return null;
        }

        // For now, use the same default avatar for all roles
        // In the future, you can create role-specific avatars:
        // - default-teacher-avatar.png
        // - default-student-avatar.png  
        // - default-institute-avatar.png
        switch ($user->role) {
            case 'teacher':
            case 'student':
            case 'institute':
            case 'university':
            case 'ngo':
            default:
                return null;
        }
    }

    /**
     * Get profile image URL with fallback to default
     * This is a convenience method that can be used in views
     */
    public function getImageUrl()
    {
        return $this->profile_image_url;
    }

    /**
     * Check if profile has a custom image
     */
    public function hasCustomImage()
    {
        $disk = config('filesystems.upload_disk', 'public');
        return !empty($this->profile_image) && Storage::disk($disk)->exists($this->profile_image);
    }

    /**
     * Get cover image URL.
     * Uses configured upload disk (GCS returns direct storage.googleapis.com URL).
     */
    public function getCoverImageUrlAttribute()
    {
        if ($this->cover_image) {
            return storage_file_url($this->cover_image);
        }
        return asset('images/default-cover.jpg');
    }

    /**
     * Update profile completion percentage
     */
    public function updateCompletionPercentage()
    {
        // Cache the completion calculation to avoid repeated database queries
        $cacheKey = "profile_completion_{$this->id}";
        
        // Check cache first
        $cachedData = \Cache::get($cacheKey);
        if ($cachedData && $cachedData['timestamp'] > now()->subMinutes(5)) {
            $this->profile_completion_percentage = $cachedData['percentage'];
            $this->profile_completion_status = $cachedData['status'];
            return $cachedData['percentage'];
        }
        
        // Ensure all relationships exist before calculating
        $this->ensureRelationshipsExist();
        
        // Get field definitions with weights
        $fieldDefinitions = $this->getFieldDefinitions();
        
        // Calculate completion efficiently
        $completionData = $this->calculateCompletionData($fieldDefinitions);
        
        // Update profile with new completion data
        $this->profile_completion_percentage = $completionData['percentage'];
        $this->profile_completion_status = $completionData['status'];
        
        // Only save if there are changes to avoid unnecessary database writes
        if ($this->isDirty(['profile_completion_percentage', 'profile_completion_status'])) {
            $this->save();
        }
        
        // Cache the result for 5 minutes
        \Cache::put($cacheKey, [
            'percentage' => $completionData['percentage'],
            'status' => $completionData['status'],
            'timestamp' => now()
        ], 300);

        return $completionData['percentage'];
    }

    /**
     * Clear profile completion cache
     */
    public function clearCompletionCache()
    {
        $cacheKey = "profile_completion_{$this->id}";
        \Cache::forget($cacheKey);
    }

    /**
     * Get field definitions with weights (Optimized)
     */
    private function getFieldDefinitions()
    {
        $baseFields = [
            'first_name' => ['weight' => 10, 'required' => true],
            'last_name' => ['weight' => 5, 'required' => false],
            'bio' => ['weight' => 10, 'required' => true],
            'phone_primary' => ['weight' => 8, 'required' => true],
            'city' => ['weight' => 5, 'required' => false],
            'state' => ['weight' => 5, 'required' => false],
            'profile_image' => ['weight' => 8, 'required' => false],
            'date_of_birth' => ['weight' => 5, 'required' => false],
            'gender_id' => ['weight' => 3, 'required' => false],
            'country_id' => ['weight' => 3, 'required' => false],
            'website' => ['weight' => 3, 'required' => false],
            'highest_qualification' => ['weight' => 8, 'required' => false],
            'institution_name' => ['weight' => 6, 'required' => false],
            'field_of_study' => ['weight' => 6, 'required' => false],
            'graduation_year' => ['weight' => 4, 'required' => false]
        ];

        $roleSpecificFields = [];
        
        if ($this->isTeacher()) {
            $roleSpecificFields = [
                'teaching_experience_years' => ['weight' => 8, 'required' => true],
                'hourly_rate_id' => ['weight' => 5, 'required' => false],
                'teaching_mode_id' => ['weight' => 5, 'required' => false],
                'subjects_taught' => ['weight' => 8, 'required' => true],
                'teaching_philosophy' => ['weight' => 5, 'required' => false]
            ];
        } elseif ($this->isStudent()) {
            $roleSpecificFields = [
                'current_class_id' => ['weight' => 8, 'required' => true],
                'current_school' => ['weight' => 8, 'required' => false],
                'board_id' => ['weight' => 5, 'required' => false],
                'stream_id' => ['weight' => 5, 'required' => false],
                'parent_name' => ['weight' => 5, 'required' => false],
                'parent_phone' => ['weight' => 5, 'required' => false],
                'budget_min' => ['weight' => 3, 'required' => false],
                'budget_max' => ['weight' => 3, 'required' => false]
            ];
        } elseif ($this->isInstitute()) {
            $roleSpecificFields = [
                'institute_name' => ['weight' => 10, 'required' => true],
                'institute_type_id' => ['weight' => 5, 'required' => true],
                'institute_category_id' => ['weight' => 5, 'required' => false],
                'establishment_year_id' => ['weight' => 3, 'required' => false],
                'principal_name' => ['weight' => 5, 'required' => false],
                'principal_phone' => ['weight' => 5, 'required' => false],
                'total_students_id' => ['weight' => 3, 'required' => false],
                'total_teachers_id' => ['weight' => 3, 'required' => false],
                'institute_description' => ['weight' => 8, 'required' => false]
            ];
        }

        return array_merge($baseFields, $roleSpecificFields);
    }

    /**
     * Calculate completion data efficiently (Optimized)
     */
    private function calculateCompletionData($fieldDefinitions)
    {
        $totalWeight = 0;
        $completedWeight = 0;
        $requiredWeight = 0;
        $completedRequiredWeight = 0;
        
        // Pre-load relationships to avoid N+1 queries
        $this->loadMissing(['teachingInfo', 'studentInfo', 'instituteInfo']);
        
        foreach ($fieldDefinitions as $field => $config) {
            $weight = $config['weight'];
            $isRequired = $config['required'];
            
            $totalWeight += $weight;
            if ($isRequired) {
                $requiredWeight += $weight;
            }
            
            if ($this->isFieldCompletedOptimized($field)) {
                $completedWeight += $weight;
                if ($isRequired) {
                    $completedRequiredWeight += $weight;
                }
            }
        }
        
        // Calculate percentage with bonus for required fields
        $basePercentage = $totalWeight > 0 ? ($completedWeight / $totalWeight) * 100 : 0;
        $requiredPercentage = $requiredWeight > 0 ? ($completedRequiredWeight / $requiredWeight) * 100 : 0;
        
        // Weighted calculation: 70% base completion + 30% required fields completion
        $percentage = round(($basePercentage * 0.7) + ($requiredPercentage * 0.3));
        
        // Determine status based on completion
        $status = $this->determineCompletionStatus($percentage, $requiredPercentage);
        
        return [
            'percentage' => min(100, max(0, $percentage)),
            'status' => $status,
            'total_weight' => $totalWeight,
            'completed_weight' => $completedWeight,
            'required_weight' => $requiredWeight,
            'completed_required_weight' => $completedRequiredWeight
        ];
    }

    /**
     * Determine completion status based on percentage
     */
    private function determineCompletionStatus($percentage, $requiredPercentage)
    {
        // Must have at least 80% of required fields completed
        if ($requiredPercentage >= 80 && $percentage >= 90) {
            return 'complete';
        } elseif ($requiredPercentage >= 60 && $percentage >= 70) {
            return 'detailed';
        } else {
            return 'basic';
        }
    }

    /**
     * Check if a specific field is completed (Optimized version)
     */
    private function isFieldCompletedOptimized($field)
    {
        // Handle related model fields efficiently
        if (str_ends_with($field, '_id') && !str_starts_with($field, 'institute_') && !str_starts_with($field, 'establishment_year_') && !str_starts_with($field, 'total_students_') && !str_starts_with($field, 'total_teachers_')) {
            $baseField = str_replace('_id', '', $field);
            
            // Check if it's a related model field
            if (method_exists($this, $baseField . 'Info')) {
                $relatedModel = $this->{$baseField . 'Info'};
                if ($relatedModel) {
                    $value = $relatedModel->$field;
                    return !empty($value) && $value > 0;
                }
                return false;
            }
        }

        // Handle special cases for related models with optimized checks
        if (in_array($field, ['teaching_experience_years', 'hourly_rate_id', 'monthly_rate_id', 'teaching_mode_id', 'travel_radius_km_id', 'availability_status_id', 'subjects_taught', 'teaching_philosophy'])) {
            $teachingInfo = $this->teachingInfo;
            if ($teachingInfo) {
                $value = $teachingInfo->$field;
                return $this->isValueCompleted($value);
            }
            return false;
        }

        // Handle education fields (stored in main profile)
        if (in_array($field, ['highest_qualification', 'institution_name', 'field_of_study', 'graduation_year', 'cgpa', 'languages_spoken'])) {
            $value = $this->$field;
            return $this->isEducationFieldCompleted($field, $value);
        }

        if (in_array($field, ['current_school', 'parent_name', 'parent_phone', 'budget_min', 'budget_max', 'learning_challenges'])) {
            $studentInfo = $this->studentInfo;
            if ($studentInfo) {
                $value = $studentInfo->$field;
                return $this->isValueCompleted($value);
            }
            return false;
        }

        if (in_array($field, ['institute_name', 'institute_type_id', 'institute_category_id', 'establishment_year_id', 'principal_name', 'principal_phone', 'total_students_id', 'total_teachers_id', 'institute_description'])) {
            $instituteInfo = $this->instituteInfo;
            if ($instituteInfo) {
                $value = $instituteInfo->$field;
                return $this->isValueCompleted($value);
            }
            return false;
        }

        // Check if the field exists in the model
        if (!property_exists($this, $field) && !$this->hasAttribute($field)) {
            return false;
        }

        try {
            $value = $this->$field;
            return $this->isValueCompleted($value);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check if a value is completed (Helper method)
     */
    private function isValueCompleted($value)
    {
        if (is_array($value) || is_object($value)) {
            return !empty($value);
        }

        if (is_string($value)) {
            return !empty(trim($value));
        }

        if (is_numeric($value)) {
            return $value > 0;
        }

        return !empty($value);
    }

    /**
     * Check if an education field is completed (Helper method)
     */
    private function isEducationFieldCompleted($field, $value)
    {
        if (is_array($value) || is_object($value)) {
            return !empty($value);
        }

        if (is_string($value)) {
            return !empty(trim($value));
        }

        if (is_numeric($value)) {
            // For graduation year, accept any year >= 1900
            if ($field === 'graduation_year') {
                return $value >= 1900 && $value <= (date('Y') + 5);
            }
            // For CGPA, accept values between 0 and 10
            if ($field === 'cgpa') {
                return $value >= 0 && $value <= 10;
            }
            // For other numeric fields, value should be > 0
            return $value > 0;
        }

        return !empty($value);
    }

    /**
     * Check if a specific field is completed (Legacy method for backward compatibility)
     */
    private function isFieldCompleted($field)
    {
        // Handle related model fields (but skip institute fields as they're handled separately)
        if (str_ends_with($field, '_id') && !str_starts_with($field, 'institute_') && !str_starts_with($field, 'establishment_year_') && !str_starts_with($field, 'total_students_') && !str_starts_with($field, 'total_teachers_')) {
            $baseField = str_replace('_id', '', $field);
            
            // Check if it's a related model field
            if (method_exists($this, $baseField . 'Info')) {
                $relatedModel = $this->{$baseField . 'Info'};
                if ($relatedModel) {
                    $value = $relatedModel->$field;
                    return !empty($value) && $value > 0;
                }
                return false;
            }
        }

        // Handle special cases for related models
        if (in_array($field, ['teaching_experience_years', 'hourly_rate', 'subjects_taught', 'grade_levels_taught', 'teaching_philosophy'])) {
            $teachingInfo = $this->teachingInfo;
            if ($teachingInfo) {
                $value = $teachingInfo->$field;
                if (is_string($value)) {
                    return !empty(trim($value));
                }
                if (is_numeric($value)) {
                    return $value > 0;
                }
                return !empty($value);
            }
            return false;
        }

        if (in_array($field, ['current_school', 'parent_name', 'parent_phone', 'budget_min', 'budget_max', 'learning_challenges'])) {
            $studentInfo = $this->studentInfo;
            if ($studentInfo) {
                $value = $studentInfo->$field;
                if (is_string($value)) {
                    return !empty(trim($value));
                }
                if (is_numeric($value)) {
                    return $value > 0;
                }
                return !empty($value);
            }
            return false;
        }

        if (in_array($field, ['institute_name', 'institute_type_id', 'institute_category_id', 'establishment_year_id', 'principal_name', 'principal_phone', 'total_students_id', 'total_teachers_id', 'institute_description'])) {
            $instituteInfo = $this->instituteInfo;
            if ($instituteInfo) {
                $value = $instituteInfo->$field;
                if (is_string($value)) {
                    return !empty(trim($value));
                }
                if (is_numeric($value)) {
                    return $value > 0;
                }
                return !empty($value);
            }
            return false;
        }

        // Check if the field exists in the model
        if (!property_exists($this, $field) && !$this->hasAttribute($field)) {
            return false;
        }

        try {
            $value = $this->$field;
        } catch (\Exception $e) {
            return false;
        }

        // Handle different field types
        if (is_array($value) || is_object($value)) {
            return !empty($value);
        }

        if (is_string($value)) {
            return !empty(trim($value));
        }

        if (is_numeric($value)) {
            return $value > 0;
        }

        return !empty($value);
    }

    /**
     * Get profile completion details (Optimized)
     */
    public function getCompletionDetails()
    {
        // Use cached field definitions to avoid duplication
        $fieldDefinitions = $this->getFieldDefinitions();
        
        // Pre-load relationships to avoid N+1 queries
        $this->loadMissing(['teachingInfo', 'studentInfo', 'instituteInfo']);
        
        $completionDetails = [];
        
        // Base fields with labels
        $baseFieldLabels = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'bio' => 'Bio/About',
            'phone_primary' => 'Phone Number',
            'city' => 'City',
            'state' => 'State',
            'profile_image' => 'Profile Picture',
            'date_of_birth' => 'Date of Birth',
            'gender_id' => 'Gender',
            'country_id' => 'Country',
            'website' => 'Website',
            'highest_qualification' => 'Highest Qualification',
            'institution_name' => 'Institution Name',
            'field_of_study' => 'Field of Study',
            'graduation_year' => 'Graduation Year'
        ];
        
        // Role-specific field labels
        $roleFieldLabels = [];
        if ($this->isTeacher()) {
            $roleFieldLabels = [
                'teaching_experience_years' => 'Teaching Experience',
                'hourly_rate_id' => 'Hourly Rate',
                'teaching_mode_id' => 'Teaching Mode',
                'subjects_taught' => 'Subjects Taught',
                'teaching_philosophy' => 'Teaching Philosophy'
            ];
        } elseif ($this->isStudent()) {
            $roleFieldLabels = [
                'current_class_id' => 'Current Class',
                'current_school' => 'Current School',
                'board_id' => 'Board',
                'stream_id' => 'Stream',
                'parent_name' => 'Parent Name',
                'parent_phone' => 'Parent Phone',
                'budget_min' => 'Budget Range',
                'budget_max' => 'Budget Range'
            ];
        } elseif ($this->isInstitute()) {
            $roleFieldLabels = [
                'institute_name' => 'Institute Name',
                'institute_type_id' => 'Institute Type',
                'institute_category_id' => 'Institute Category',
                'establishment_year_id' => 'Establishment Year',
                'principal_name' => 'Principal Name',
                'principal_phone' => 'Principal Phone',
                'total_students_id' => 'Total Students',
                'total_teachers_id' => 'Total Teachers',
                'institute_description' => 'Institute Description'
            ];
        }
        
        $allFieldLabels = array_merge($baseFieldLabels, $roleFieldLabels);
        
        // Build completion details efficiently
        foreach ($fieldDefinitions as $field => $config) {
            $completionDetails[$field] = [
                'label' => $allFieldLabels[$field] ?? ucfirst(str_replace('_', ' ', $field)),
                'weight' => $config['weight'],
                'required' => $config['required'],
                'completed' => $this->isFieldCompletedOptimized($field)
            ];
        }
        
        return $completionDetails;
    }

    /**
     * Get completion status text
     */
    public function getCompletionStatusText()
    {
        switch ($this->profile_completion_status) {
            case 'complete':
                return 'Complete';
            case 'detailed':
                return 'Detailed';
            case 'basic':
                return 'Basic';
            default:
                return 'Incomplete';
        }
    }

    /**
     * Get completion status color
     */
    public function getCompletionStatusColor()
    {
        switch ($this->profile_completion_status) {
            case 'complete':
                return 'success';
            case 'detailed':
                return 'info';
            case 'basic':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    /**
     * Get field completion status for frontend display
     */
    public function getFieldCompletionStatus()
    {
        $completionDetails = $this->getCompletionDetails();
        $fieldStatus = [];
        
        foreach ($completionDetails as $field => $details) {
            $fieldStatus[$field] = [
                'label' => $details['label'],
                'weight' => $details['weight'],
                'completed' => $details['completed'],
                'icon' => $details['completed'] ? 'bi-check-circle-fill text-success' : 'bi-circle text-muted',
                'status' => $details['completed'] ? 'Completed' : 'Incomplete',
                'priority' => $details['weight'] >= 8 ? 'high' : ($details['weight'] >= 5 ? 'medium' : 'low')
            ];
        }
        
        return $fieldStatus;
    }

     /**
     * Get API field completion status for frontend display
     */
    public function getFieldCompletionStatusApi()
    {
        $completionDetails = $this->getCompletionDetails();
        $fieldStatus = [];
        
        foreach ($completionDetails as $field => $details) {
            $fieldStatus[$field] = [
                'label' => $details['label'],
                'completed' => $details['completed'],
            ];
        }
        
        return $fieldStatus;
    }

    /**
     * Get completion summary for quick display
     */
    public function getCompletionSummary()
    {
        $completionDetails = $this->getCompletionDetails();
        $totalFields = count($completionDetails);
        $completedFields = 0;
        $highPriorityCompleted = 0;
        $highPriorityTotal = 0;
        
        foreach ($completionDetails as $field => $details) {
            if ($details['completed']) {
                $completedFields++;
            }
            if ($details['weight'] >= 8) {
                $highPriorityTotal++;
                if ($details['completed']) {
                    $highPriorityCompleted++;
                }
            }
        }
        
        return [
            'total_fields' => $totalFields,
            'completed_fields' => $completedFields,
            'completion_percentage' => $this->profile_completion_percentage,
            'high_priority_completed' => $highPriorityCompleted,
            'high_priority_total' => $highPriorityTotal,
            'status' => $this->getCompletionStatusText(),
            'color' => $this->getCompletionStatusColor(),
            'next_priority_fields' => $this->getNextPriorityFields()
        ];
    }

    /**
     * Get next priority fields to complete
     */
    private function getNextPriorityFields()
    {
        $completionDetails = $this->getCompletionDetails();
        $incompleteFields = [];
        
        foreach ($completionDetails as $field => $details) {
            if (!$details['completed']) {
                $incompleteFields[] = [
                    'field' => $field,
                    'label' => $details['label'],
                    'weight' => $details['weight'],
                    'priority' => $details['weight'] >= 8 ? 'high' : ($details['weight'] >= 5 ? 'medium' : 'low')
                ];
            }
        }
        
        // Sort by weight (priority) descending
        usort($incompleteFields, function($a, $b) {
            return $b['weight'] - $a['weight'];
        });
        
        return array_slice($incompleteFields, 0, 5); // Return top 5 priority fields
    }

    /**
     * Scope for verified profiles
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Scope for active profiles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for featured profiles
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope for profiles by location
     */
    public function scopeByLocation($query, $city = null, $state = null)
    {
        if ($city) {
            $query->where('city', 'like', "%{$city}%");
        }
        if ($state) {
            $query->where('state', 'like', "%{$state}%");
        }
        return $query;
    }

    /**
     * Scope for teachers by subject
     */
    public function scopeBySubject($query, $subject)
    {
        return $query->whereJsonContains('subjects_taught', $subject);
    }

    /**
     * Scope for teachers by grade level
     */
    public function scopeByGradeLevel($query, $gradeLevel)
    {
        return $query->whereJsonContains('grade_levels_taught', $gradeLevel);
    }

    /**
     * Increment profile views
     */
    public function incrementViews()
    {
        $this->increment('profile_views');
        $this->update(['last_activity_at' => now()]);
    }

    /**
     * Update location from coordinates
     */
    public function updateLocationFromCoordinates($latitude, $longitude)
    {
        // This would typically use a geocoding service like Google Maps API
        // For now, we'll just store the coordinates
        $this->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'location_auto_detected' => true,
            'location_last_updated' => now(),
        ]);
    }

    /**
     * Ensure all profile relationships are properly initialized
     */
    public function ensureRelationshipsExist()
    {
        try {
            $user = $this->user;
            
            // Create relationships based on user role
            if ($user && $user->isInstitute()) {
                $this->getOrCreateInstituteInfo();
            }
            
            if ($user && $user->isStudent()) {
                $this->getOrCreateStudentInfo();
            }
            
            if ($user && $user->isTeacher()) {
                $this->getOrCreateTeachingInfo();
            }
            
            // Always create professional info
            $this->getOrCreateProfessionalInfo();
            
            // Refresh the model to load all relationships
            $this->refresh();
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to ensure profile relationships exist: ' . $e->getMessage(), [
                'profile_id' => $this->id,
                'user_id' => $this->user_id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Force refresh completion calculation
     */
    public function forceRefreshCompletion()
    {
        // Ensure relationships exist
        $this->ensureRelationshipsExist();
        
        // Force reload the model with relationships
        $this->load(['instituteInfo', 'studentInfo', 'teachingInfo', 'professionalInfo']);
        
        // Update completion percentage
        return $this->updateCompletionPercentage();
    }

    /**
     * Get all profile data as an array with relationships
     */
    public function getAllProfileData()
    {
        try {
            $this->ensureRelationshipsExist();
            
            return [
                'profile' => $this->toArray(),
                'institute_info' => $this->instituteInfo ? $this->instituteInfo->toArray() : null,
                'student_info' => $this->studentInfo ? $this->studentInfo->toArray() : null,
                'teaching_info' => $this->teachingInfo ? $this->teachingInfo->toArray() : null,
                'professional_info' => $this->professionalInfo ? $this->professionalInfo->toArray() : null,
                'social_links' => $this->socialLinks ? $this->socialLinks->toArray() : [],
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get all profile data: ' . $e->getMessage());
            return [
                'profile' => $this->toArray(),
                'error' => 'Failed to load some profile data'
            ];
        }
    }


} 