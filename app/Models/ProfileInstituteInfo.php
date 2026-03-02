<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileInstituteInfo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'profile_institute_info';

    protected $fillable = [
        'profile_id',
        'institute_name',
        'institute_type_id',
        'institute_category_id',
        'affiliation_number',
        'registration_number',
        'udise_code',
        'aicte_code',
        'ugc_code',
        'establishment_year_id',
        'principal_name',
        'principal_phone',
        'principal_email',
        'total_students_id',
        'total_teachers_id',
        'total_branches',
        'facilities',
        'accreditations',
        'affiliations',
        'institute_description',
        'courses_offered',
        'specializations',
        'is_main_branch',
        'parent_institute_id',
    ];

    protected $casts = [
        'facilities' => 'array',
        'accreditations' => 'array',
        'affiliations' => 'array',
        'courses_offered' => 'array',
        'specializations' => 'array',
        'is_main_branch' => 'boolean',
    ];

    /**
     * Get the profile that owns the institute info
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }

    /**
     * Get the parent institute (for branches)
     */
    public function parentInstitute()
    {
        return $this->belongsTo(ProfileInstituteInfo::class, 'parent_institute_id');
    }

    /**
     * Get the branch institutes
     */
    public function branches()
    {
        return $this->hasMany(ProfileInstituteInfo::class, 'parent_institute_id');
    }

    /**
     * Get the institute type display name
     */
    public function getInstituteTypeNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('institute_type', $this->institute_type_id);
    }

    /**
     * Get the institute category display name
     */
    public function getInstituteCategoryNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('institute_category', $this->institute_category_id);
    }

    /**
     * Get the establishment year display name
     */
    public function getEstablishmentYearNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('establishment_year_range', $this->establishment_year_id);
    }

    /**
     * Get the total students range display name
     */
    public function getTotalStudentsNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('total_students_range', $this->total_students_id);
    }

    /**
     * Get the total teachers range display name
     */
    public function getTotalTeachersNameAttribute()
    {
        return \App\Helpers\ProfileOptionsHelper::getLabel('total_teachers_range', $this->total_teachers_id);
    }
} 