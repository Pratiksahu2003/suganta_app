<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileProfessionalInfo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'profile_professional_info';

    protected $fillable = [
        'profile_id',
        'profession',
        'company_name',
        'job_title',
        'department',
        'work_experience',
        'skills',
        'certifications',
        'awards',
        'publications',
        'research_interests',
    ];

    protected $casts = [
        'skills' => 'array',
        'certifications' => 'array',
        'awards' => 'array',
        'publications' => 'array',
        'research_interests' => 'array',
    ];

    /**
     * Get the profile that owns the professional info
     */
    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
} 