<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExamPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'status',
        'description',
        'price',
        'duration',
        'features',
        'is_active'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get teachers who offer this exam package
     */
    public function teachers()
    {
        return $this->belongsToMany(TeacherProfile::class, 'exam_package_teacher', 'exam_package_id', 'teacher_profile_id')
                    ->withPivot(['duration', 'price'])
                    ->withTimestamps();
    }

    /**
     * Scope for active packages
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')->orWhere('is_active', true);
    }
} 