<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Institute extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'institute_name',
        'slug',
        'description',
        'specialization',
        'affiliation',
        'registration_number',
        'website',
        'contact_person',
        'contact_phone',
        'contact_email',
        'address',
        'city',
        'state',
        'pincode',
        'latitude',
        'longitude',
        'facilities',
        'established_year',
        'total_students',
        'rating',
        'verified',
        'verification_status',
        'is_featured',
        'logo',
        'gallery_images',
        // Branch hierarchy fields
        'parent_institute_id',
        'institute_type',
        'branch_name',
        'branch_code',
        'branch_description',
        'branch_manager_id',
        'operating_hours',
        'branch_area_sqft',
        'max_students_capacity',
        'is_active_branch',
        'branch_phone',
        'branch_email',
        'branch_address',
        'branch_city',
        'branch_state',
        'branch_pincode',
        'branch_latitude',
        'branch_longitude',
        'level',
        'hierarchy_path',
        'total_branches',
        'total_sub_branches',
        'branch_order',
        'branch_established_date',
        'branch_facilities',
        'branch_gallery_images',
    ];

    protected $casts = [
        'facilities' => 'array',
        'gallery_images' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'rating' => 'decimal:2',
        'verified' => 'boolean',
        'operating_hours' => 'array',
        'branch_area_sqft' => 'decimal:2',
        'is_active_branch' => 'boolean',
        'branch_latitude' => 'decimal:8',
        'branch_longitude' => 'decimal:8',
        'branch_established_date' => 'datetime',
        'branch_facilities' => 'array',
        'branch_gallery_images' => 'array',
    ];

    /**
     * Get the user that owns the institute
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent institute
     */
    public function parentInstitute()
    {
        return $this->belongsTo(Institute::class, 'parent_institute_id');
    }

    /**
     * Get the child branches
     */
    public function childBranches()
    {
        return $this->hasMany(Institute::class, 'parent_institute_id');
    }

    /**
     * Get all descendant branches (recursive)
     */
    public function allDescendants()
    {
        return $this->hasMany(Institute::class, 'parent_institute_id')->with('allDescendants');
    }

    /**
     * Get the branch manager
     */
    public function branchManager()
    {
        return $this->belongsTo(User::class, 'branch_manager_id');
    }

    /**
     * Get the teachers that belong to this institute/branch
     */
    public function teachers()
    {
        return $this->hasMany(TeacherProfile::class);
    }

    /**
     * Get teachers at this specific branch
     */
    public function branchTeachers()
    {
        return $this->hasMany(TeacherProfile::class, 'branch_id');
    }

    /**
     * Get verified teachers at this institute/branch
     */
    public function verifiedTeachers()
    {
        return $this->hasMany(TeacherProfile::class)->where('verification_status', 'verified');
    }

    /**
     * Get verified teachers at this specific branch
     */
    public function verifiedBranchTeachers()
    {
        return $this->hasMany(TeacherProfile::class, 'branch_id')->where('verification_status', 'verified');
    }

    /**
     * Get pending teachers at this institute
     */
    public function pendingTeachers()
    {
        return $this->hasMany(TeacherProfile::class)->where('verification_status', 'pending');
    }

    /**
     * Get all teachers in this institute and all its branches
     */
    public function allTeachers()
    {
        $teacherIds = collect();
        
        // Get teachers from main institute
        $teacherIds = $teacherIds->merge($this->teachers()->pluck('id'));
        
        // Get teachers from all branches recursively
        $this->getAllBranchTeachers($this, $teacherIds);
        
        return TeacherProfile::whereIn('id', $teacherIds);
    }

    /**
     * Recursively get teachers from all branches
     */
    private function getAllBranchTeachers($institute, &$teacherIds)
    {
        foreach ($institute->childBranches as $branch) {
            $teacherIds = $teacherIds->merge($branch->branchTeachers()->pluck('id'));
            $this->getAllBranchTeachers($branch, $teacherIds);
        }
    }

    /**
     * Get the subjects offered by the institute
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'institute_subjects')
                    ->withPivot([
                        'course_duration', 'fees', 'course_description', 'teaching_mode',
                        'batch_size', 'timings', 'status'
                    ])
                    ->withTimestamps();
    }

    /**
     * Get the reviews for this institute
     */
    public function reviews()
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    /**
     * Get the exams that the institute prepares students for
     */
    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'institute_exams')
                    ->withPivot([
                        'course_name', 'course_duration_months', 'batch_size', 'success_rate',
                        'students_cleared', 'fee_range_min', 'fee_range_max', 'course_description',
                        'course_features', 'study_materials', 'faculty_details', 'teaching_mode',
                        'schedule_details', 'facilities', 'admission_requirements', 'course_start_date',
                        'course_end_date', 'admission_deadline', 'scholarship_available',
                        'scholarship_details', 'placement_assistance', 'achievements', 'status'
                    ])
                    ->withTimestamps();
    }

    /**
     * Get subjects taught by institute teachers
     */
    public function teacherSubjects()
    {
        $teacherIds = $this->teachers()->pluck('id');
        return Subject::whereHas('teachers', function ($query) use ($teacherIds) {
            $query->whereIn('teacher_profiles.id', $teacherIds);
        })->distinct();
    }

    /**
     * Check if this is a main institute
     */
    public function isMainInstitute(): bool
    {
        return $this->institute_type === 'main' && is_null($this->parent_institute_id);
    }

    /**
     * Check if this is a branch
     */
    public function isBranch(): bool
    {
        return $this->institute_type === 'branch';
    }

    /**
     * Check if this is a sub-branch
     */
    public function isSubBranch(): bool
    {
        return $this->institute_type === 'sub_branch';
    }

    /**
     * Get the root (main) institute
     */
    public function getRootInstitute()
    {
        if ($this->isMainInstitute()) {
            return $this;
        }
        
        return $this->parentInstitute->getRootInstitute();
    }

    /**
     * Get the display name (includes branch name if applicable)
     */
    public function getDisplayNameAttribute()
    {
        if ($this->isMainInstitute()) {
            return $this->institute_name;
        }
        
        return $this->institute_name . ($this->branch_name ? ' - ' . $this->branch_name : '');
    }

    /**
     * Get the hierarchy breadcrumb
     */
    public function getHierarchyBreadcrumbAttribute()
    {
        $breadcrumb = [];
        $current = $this;
        
        while ($current) {
            array_unshift($breadcrumb, [
                'id' => $current->id,
                'name' => $current->isMainInstitute() ? $current->institute_name : $current->branch_name,
                'type' => $current->institute_type
            ]);
            $current = $current->parentInstitute;
        }
        
        return $breadcrumb;
    }

    /**
     * Check if institute is verified
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * Check if branch is active
     */
    public function isActiveBranch(): bool
    {
        return $this->is_active_branch;
    }

    /**
     * Get total number of teachers in this institute/branch only
     */
    public function getTotalTeachersAttribute()
    {
        return $this->teachers()->count();
    }

    /**
     * Get total number of teachers in all branches (for main institute)
     */
    public function getTotalAllTeachersAttribute()
    {
        if ($this->isMainInstitute()) {
            return $this->allTeachers()->count();
        }
        return $this->getTotalTeachersAttribute();
    }

    /**
     * Get total number of verified teachers
     */
    public function getVerifiedTeachersCountAttribute()
    {
        return $this->verifiedTeachers()->count();
    }

    /**
     * Get full address for this branch
     */
    public function getFullAddressAttribute()
    {
        if ($this->branch_address) {
            return $this->branch_address . ', ' . $this->branch_city . ', ' . $this->branch_state . ' - ' . $this->branch_pincode;
        }
        
        return $this->address . ', ' . $this->city . ', ' . $this->state . ' - ' . $this->pincode;
    }

    /**
     * Check if user can manage this institute/branch
     */
    public function canBeManaged($user): bool
    {
        // Owner can manage
        if ($user->id === $this->user_id) {
            return true;
        }
        
        // Branch manager can manage this branch
        if ($user->id === $this->branch_manager_id) {
            return true;
        }
        
        // Parent institute owner can manage child branches
        if ($this->parentInstitute && $user->id === $this->parentInstitute->user_id) {
            return true;
        }
        
        // Admin can manage
        return $user->isAdmin();
    }

    /**
     * Create a new branch
     */
    public function createBranch(array $branchData, $branchType = 'branch')
    {
        $branchData['parent_institute_id'] = $this->id;
        $branchData['institute_type'] = $branchType;
        $branchData['level'] = $this->level + 1;
        $branchData['hierarchy_path'] = ($this->hierarchy_path ?? '/' . $this->id) . '/' . $this->id;
        
        // Generate unique branch code if not provided
        if (!isset($branchData['branch_code'])) {
            $branchData['branch_code'] = $this->generateBranchCode();
        }
        
        $branch = Institute::create($branchData);
        
        // Update parent branch counts
        $this->updateBranchCounts();
        
        return $branch;
    }

    /**
     * Generate unique branch code
     */
    private function generateBranchCode()
    {
        $rootInstitute = $this->getRootInstitute();
        $prefix = strtoupper(substr($rootInstitute->institute_name, 0, 3));
        $number = $rootInstitute->total_sub_branches + 1;
        
        return $prefix . '-BR-' . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Update branch counts for this institute
     */
    public function updateBranchCounts()
    {
        $this->total_branches = $this->childBranches()->count();
        $this->total_sub_branches = $this->calculateTotalSubBranches();
        $this->save();
        
        // Update parent counts recursively
        if ($this->parentInstitute) {
            $this->parentInstitute->updateBranchCounts();
        }
    }

    /**
     * Calculate total sub-branches recursively
     */
    private function calculateTotalSubBranches()
    {
        $count = 0;
        foreach ($this->childBranches as $branch) {
            $count += 1 + $branch->calculateTotalSubBranches();
        }
        return $count;
    }

    /**
     * Verify a teacher at this institute/branch
     */
    public function verifyTeacher(TeacherProfile $teacher): bool
    {
        if ($teacher->institute_id === $this->id || $teacher->branch_id === $this->id) {
            return $teacher->update(['verification_status' => 'verified']);
        }
        return false;
    }

    /**
     * Verify a teacher at this branch specifically
     */
    public function verifyBranchTeacher(TeacherProfile $teacher): bool
    {
        if ($teacher->branch_id === $this->id) {
            return $teacher->update(['verification_status' => 'verified']);
        }
        return false;
    }

    /**
     * Remove teacher verification
     */
    public function unverifyTeacher(TeacherProfile $teacher): bool
    {
        if ($teacher->institute_id === $this->id || $teacher->branch_id === $this->id) {
            return $teacher->update(['verification_status' => 'pending']);
        }
        return false;
    }

    /**
     * Scope to get only main institutes
     */
    public function scopeMainInstitutes($query)
    {
        return $query->where('institute_type', 'main');
    }

    /**
     * Scope to get only branches
     */
    public function scopeBranches($query)
    {
        return $query->where('institute_type', 'branch');
    }

    /**
     * Scope to get active branches
     */
    public function scopeActiveBranches($query)
    {
        return $query->where('is_active_branch', true);
    }

    /**
     * Scope to get branches of a specific parent
     */
    public function scopeChildrenOf($query, $parentId)
    {
        return $query->where('parent_institute_id', $parentId);
    }

    /**
     * Get the branches of this institute
     */
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    /**
     * Get the students enrolled at this institute (through branches)
     */
    public function students()
    {
        $branchIds = $this->branches()->pluck('id');
        return StudentProfile::whereHas('branches', function($query) use ($branchIds) {
            $query->whereIn('branch_student.branch_id', $branchIds);
        });
    }

    /**
     * Get the sessions associated with this institute
     */
    public function sessions()
    {
        return $this->hasManyThrough(
            \App\Models\TeacherSession::class,
            \App\Models\TeacherProfile::class,
            'institute_id', // Foreign key on teacher_profiles table
            'teacher_profile_id', // Foreign key on teacher_sessions table
            'id', // Local key on institutes table
            'id' // Local key on teacher_profiles table
        )->select(['teacher_sessions.*', 'teacher_profiles.institute_id']);
    }

    /**
     * Get the courses associated with this institute
     * Note: Course model doesn't exist yet, returning empty collection
     */
    public function courses()
    {
        // Placeholder - Course model doesn't exist yet
        return collect();
    }

    /**
     * Get the exam types for this institute
     */
    public function examTypes()
    {
        return $this->belongsToMany(Exam::class, 'institute_exams')
            ->withPivot(['courses_offered', 'success_rate', 'batch_size'])
            ->withTimestamps();
    }


    /**
     * Get total students count
     */
    public function getTotalStudentsAttribute()
    {
        return $this->students()->count();
    }

    /**
     * Get total branches count
     */
    public function getTotalBranchesAttribute()
    {
        return $this->branches()->count();
    }
}
