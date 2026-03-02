<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolBranch extends Model
{
    use HasFactory;

    protected $fillable = [
        'teacher_id',
        'school_name',
        'branch_name',
        'address',
        'city',
        'state',
        'pincode',
        'phone',
        'email',
        'website',
        'established_year',
        'affiliation',
        'board',
        'medium_of_instruction',
        'facilities',
        'timings',
        'contact_person',
        'contact_designation',
        'is_primary',
        'status',
        'latitude',
        'longitude',
        'google_maps_link',
        'photos',
        'documents',
    ];

    protected $casts = [
        'established_year' => 'integer',
        'is_primary' => 'boolean',
        'facilities' => 'array',
        'timings' => 'array',
        'photos' => 'array',
        'documents' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the teacher who owns this school branch
     */
    public function teacher()
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    /**
     * Get subjects offered at this branch
     */
    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'school_branch_subjects')
                   ->withPivot(['classes_offered', 'fee_per_month', 'batch_size', 'duration'])
                   ->withTimestamps();
    }

    /**
     * Get exams prepared at this branch
     */
    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'school_branch_exams')
                   ->withPivot(['course_duration', 'fee_range', 'success_rate', 'batch_size'])
                   ->withTimestamps();
    }

    /**
     * Get enrollments for this branch (commented out until Enrollment model is created)
     */
    // public function enrollments()
    // {
    //     return $this->hasMany(Enrollment::class, 'school_branch_id');
    // }

    /**
     * Get reviews for this branch (commented out until Review model is created)
     */
    // public function reviews()
    // {
    //     return $this->hasMany(Review::class, 'school_branch_id');
    // }

    /**
     * Scope for active branches
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for primary branches
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope for branches by city
     */
    public function scopeByCity($query, $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    /**
     * Scope for branches by state
     */
    public function scopeByState($query, $state)
    {
        return $query->where('state', 'like', "%{$state}%");
    }

    /**
     * Get branch display name
     */
    public function getDisplayNameAttribute()
    {
        if ($this->branch_name && $this->branch_name !== $this->school_name) {
            return $this->school_name . ' - ' . $this->branch_name;
        }
        
        return $this->school_name;
    }

    /**
     * Get full address
     */
    public function getFullAddressAttribute()
    {
        $address = collect([$this->address, $this->city, $this->state, $this->pincode])
                  ->filter()
                  ->join(', ');
        
        return $address;
    }

    /**
     * Get board display name
     */
    public function getBoardDisplayAttribute()
    {
        return match($this->board) {
            'cbse' => 'CBSE',
            'icse' => 'ICSE',
            'state_board' => 'State Board',
            'ib' => 'International Baccalaureate (IB)',
            'cambridge' => 'Cambridge International',
            'nios' => 'NIOS',
            'other' => 'Other',
            default => 'Not Specified'
        };
    }

    /**
     * Get affiliation display name
     */
    public function getAffiliationDisplayAttribute()
    {
        return match($this->affiliation) {
            'government' => 'Government',
            'government_aided' => 'Government Aided',
            'private_unaided' => 'Private Unaided',
            'private_aided' => 'Private Aided',
            'central_government' => 'Central Government',
            'autonomous' => 'Autonomous',
            'international' => 'International',
            default => 'Not Specified'
        };
    }

    /**
     * Get medium of instruction display
     */
    public function getMediumDisplayAttribute()
    {
        if (is_array($this->medium_of_instruction)) {
            return implode(', ', $this->medium_of_instruction);
        }
        
        return $this->medium_of_instruction ?: 'Not Specified';
    }

    /**
     * Get operating hours display
     */
    public function getOperatingHoursAttribute()
    {
        if (!$this->timings) {
            return 'Not Specified';
        }
        
        $morning = $this->timings['morning'] ?? null;
        $evening = $this->timings['evening'] ?? null;
        
        $hours = [];
        if ($morning) {
            $hours[] = "Morning: {$morning['start']} - {$morning['end']}";
        }
        if ($evening) {
            $hours[] = "Evening: {$evening['start']} - {$evening['end']}";
        }
        
        return implode(', ', $hours) ?: 'Not Specified';
    }



    /**
     * Check if branch has facility
     */
    public function hasFacility($facility)
    {
        return in_array($facility, $this->facilities ?: []);
    }

    /**
     * Get available facilities list
     */
    public function getAvailableFacilitiesAttribute()
    {
        $allFacilities = [
            'library' => 'Library',
            'computer_lab' => 'Computer Lab',
            'science_lab' => 'Science Lab',
            'smart_classrooms' => 'Smart Classrooms',
            'wifi' => 'WiFi',
            'parking' => 'Parking',
            'canteen' => 'Canteen',
            'playground' => 'Playground',
            'sports_facilities' => 'Sports Facilities',
            'medical_room' => 'Medical Room',
            'transport' => 'Transport',
            'hostel' => 'Hostel',
            'ac_classrooms' => 'AC Classrooms',
            'auditorium' => 'Auditorium',
            'counseling' => 'Counseling Services',
        ];
        
        $facilities = $this->facilities ?: [];
        
        return collect($facilities)->map(function ($facility) use ($allFacilities) {
            return $allFacilities[$facility] ?? $facility;
        })->values()->toArray();
    }

    /**
     * Get distance from coordinates
     */
    public function getDistanceFrom($latitude, $longitude)
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }
        
        $earthRadius = 6371; // km
        
        $dLat = deg2rad($this->latitude - $latitude);
        $dLon = deg2rad($this->longitude - $longitude);
        
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude)) * cos(deg2rad($this->latitude)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return round($earthRadius * $c, 2);
    }

    /**
     * Get branch statistics
     */
    public function getStatsAttribute()
    {
        return [
            'total_students' => $this->total_students,
            'total_subjects' => $this->subjects()->count(),
            'total_exams' => $this->exams()->count(),
            'average_rating' => round($this->rating, 1),
            'total_reviews' => $this->reviews_count,
            'established_years' => $this->established_year ? (now()->year - $this->established_year) : 0,
        ];
    }

    /**
     * Mark as primary branch
     */
    public function markAsPrimary()
    {
        // Unmark other branches as primary for this teacher
        static::where('teacher_profile_id', $this->teacher_profile_id)
              ->where('id', '!=', $this->id)
              ->update(['is_primary' => false]);
              
        $this->update(['is_primary' => true]);
        
        return $this;
    }

    /**
     * Get nearby branches
     */
    public function getNearbyBranches($radius = 10)
    {
        if (!$this->latitude || !$this->longitude) {
            return collect([]);
        }
        
        return static::active()
                    ->where('id', '!=', $this->id)
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->get()
                    ->filter(function ($branch) use ($radius) {
                        $distance = $this->getDistanceFrom($branch->latitude, $branch->longitude);
                        return $distance && $distance <= $radius;
                    })
                    ->sortBy(function ($branch) {
                        return $this->getDistanceFrom($branch->latitude, $branch->longitude);
                    });
    }
} 