<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProfileTeachingInfo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'profile_teaching_info';

    protected $fillable = [
        'profile_id',
        'teaching_experience_years',
        'total_students_taught',
        'hourly_rate',
        'hourly_rate_id',
        'monthly_rate',
        'monthly_rate_id',
        'teaching_mode_id',
        'online_classes',
        'home_tuition',
        'institute_classes',
        'travel_radius_km',
        'travel_radius_km_id',
        'subjects_taught',
        'grade_levels_taught',
        'exam_preparation',
        'teaching_philosophy',
        'teaching_methods',
        'available_timings',
        'availability_status_id',
        'qualification',
        'bio',
        'experience_years',
        'specialization',
        'languages',
        'teaching_mode',
        'institute_id',
        'employment_type',
        'institute_experience',
        'institute_subjects',
        'is_institute_verified',
        'verified',
        'rating',
        'total_students',
        'employment_status',
        'slug',
    ];

    protected $casts = [
        'hourly_rate' => 'decimal:2',
        'monthly_rate' => 'decimal:2',
        'online_classes' => 'boolean',
        'home_tuition' => 'boolean',
        'institute_classes' => 'boolean',
        'subjects_taught' => 'array',
        'grade_levels_taught' => 'array',
        'exam_preparation' => 'array',
        'teaching_methods' => 'array',
        'available_timings' => 'array',
    ];


    /**
     * Get the teaching mode display name
     */
    public function getTeachingModeNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('teaching_mode', $this->teaching_mode_id);
    }

    /**
     * Get the availability status display name
     */
    public function getAvailabilityStatusNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('availability_status', $this->availability_status_id);
    }

    /**
     * Get all sessions created by this teacher
     */
    public function sessions()
    {
        return $this->hasMany(\App\Models\TeacherSession::class, 'teacher_profile_id');
    }

    /**
     * Get upcoming sessions
     */
    public function upcomingSessions()
    {
        return $this->sessions()->where('status', 'scheduled')->where('date', '>=', now()->toDateString());
    }

    /**
     * Get completed sessions
     */
    public function completedSessions()
    {
        return $this->sessions()->where('status', 'completed');
    }

    /**
     * Get cancelled sessions
     */
    public function cancelledSessions()
    {
        return $this->sessions()->where('status', 'cancelled');
    }

    /**
     * Get today's sessions
     */
    public function todaySessions()
    {
        return $this->sessions()->where('date', now()->toDateString());
    }

    /**
     * Get the subjects taught by this teacher
     * Uses the subjects_taught array column to fetch related subjects
     * This is an accessor, not a relationship, to avoid eager loading issues
     */
    public function getSubjectsAttribute()
    {
        $subjectIds = $this->subjects_taught;
        
        if (empty($subjectIds)) {
            return collect([]);
        }
        
        // Convert to array if it's stored as JSON
        if (is_string($subjectIds)) {
            $subjectIds = json_decode($subjectIds, true);
        }
        
        if (empty($subjectIds) || !is_array($subjectIds)) {
            return collect([]);
        }
        
        return \App\Models\Subject::whereIn('id', $subjectIds)->get();
    }
    
    /**
     * Relationship method for compatibility (but returns empty to prevent eager loading errors)
     * Use $profileTeachingInfo->subjects (accessor) instead
     */
    public function subjects()
    {
        // Return a relationship that will always be empty
        // This prevents errors when Laravel tries to eager load
        // Use the accessor $model->subjects instead
        return $this->hasMany(\App\Models\Subject::class, 'id', 'id')
            ->whereRaw('1 = 0');
    }
    
    /**
     * Get subjects as a collection (accessor)
     * This provides a convenient way to access subjects without eager loading
     */
    public function getSubjectsCollectionAttribute()
    {
        $subjectIds = $this->subjects_taught;
        
        if (empty($subjectIds)) {
            return collect([]);
        }
        
        // Convert to array if it's stored as JSON
        if (is_string($subjectIds)) {
            $subjectIds = json_decode($subjectIds, true);
        }
        
        if (empty($subjectIds) || !is_array($subjectIds)) {
            return collect([]);
        }
        
        return \App\Models\Subject::whereIn('id', $subjectIds)->get();
    }

    /**
     * Get the names of all subjects taught by this teacher (helper method)
     * The subject IDs are stored in the 'subjects_taught' column as an array.
     * Returns an array of subject names.
     */
    public function getSubjectNames($subjects_taught = null)
    {
        $subjectIds = $subjects_taught ?? $this->subjects_taught;
        
        if (empty($subjectIds)) {
            return [];
        }
        
        // Convert to array if it's stored as JSON
        if (is_string($subjectIds)) {
            $subjectIds = json_decode($subjectIds, true);
        }
        
        if (empty($subjectIds) || !is_array($subjectIds)) {
            return [];
        }
        
        return \App\Models\Subject::whereIn('id', $subjectIds)->pluck('name')->toArray();
    }

    /**
     * Get the exam packages offered by this teacher
     */
    public function examPackages()
    {
        return $this->belongsToMany(\App\Models\ExamPackage::class, 'teacher_exam_packages', 'teacher_profile_id', 'exam_package_id')
            ->withPivot(['duration', 'price'])
            ->withTimestamps();
    }

    /**
     * Get the institute this teacher belongs to
     */
    public function institute()
    {
        return $this->belongsTo(\App\Models\Institute::class, 'institute_id');
    }

    /**
     * Get reviews for this teacher
     */
    public function reviews()
    {
        return $this->morphMany(\App\Models\Review::class, 'reviewable');
    }

    /**
     * Get the user that owns this profile
     */
    public function user()
    {
        return $this->hasOneThrough(
            \App\Models\User::class,
            \App\Models\Profile::class,
            'id', // Foreign key on profiles table
            'id', // Foreign key on users table
            'profile_id', // Local key on profile_teaching_info table
            'user_id' // Local key on profiles table
        );
    }

    /**
     * Get subjects as array for compatibility
     */
    public function subjectpackage()
    {
        return $this->subjects()->pluck('subjects.id');
    }

    /**
     * Get exam packages as array for compatibility
     */
    public function examPackagesList()
    {
        return $this->examPackages()->pluck('exam_packages.id');
    }

    /**
     * Get the profile that owns this teaching info
     */
    public function profile()
    {
        return $this->belongsTo(\App\Models\Profile::class);
    }

    /**
     * Get the slug attribute (generate if not exists)
     */
    public function getSlugAttribute()
    {
        if (empty($this->attributes['slug']) && $this->user) {
            $slug = \Illuminate\Support\Str::slug($this->user->name);
            $this->update(['slug' => $slug]);
            return $slug;
        }
        return $this->attributes['slug'] ?? null;
    }

    /**
     * Get the verified attribute (default to false)
     */
    public function getVerifiedAttribute()
    {
        return $this->attributes['verified'] ?? false;
    }

    /**
     * Get the rating attribute (default to 0)
     */
    public function getRatingAttribute()
    {
        return $this->attributes['rating'] ?? 0;
    }

    /**
     * Get the total_students attribute (default to 0)
     */
    public function getTotalStudentsAttribute()
    {
        return $this->attributes['total_students'] ?? 0;
    }

    /**
     * Get the is_institute_verified attribute (default to false)
     */
    public function getIsInstituteVerifiedAttribute()
    {
        return $this->attributes['is_institute_verified'] ?? false;
    }

    /**
     * Get the employment_status attribute (default to 'freelance')
     */
    public function getEmploymentStatusAttribute()
    {
        return $this->attributes['employment_status'] ?? 'freelance';
    }
} 