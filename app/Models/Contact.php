<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'ip_address',
        'user_agent',
        'responded_at',
        'notes',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Scope for new contacts
     */
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    /**
     * Scope for responded contacts
     */
    public function scopeResponded($query)
    {
        return $query->where('status', 'responded');
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'new' => 'warning',
            'responded' => 'success',
            'closed' => 'secondary',
            default => 'primary'
        };
    }

    /**
     * Get inquiry type display name
     */
    public function getInquiryTypeDisplayAttribute()
    {
        return match($this->inquiry_type) {
            'general' => 'General Inquiry',
            'teacher' => 'Teacher Inquiry',
            'institute' => 'Institute Inquiry',
            'support' => 'Support',
            'partnership' => 'Partnership',
            default => 'General'
        };
    }
} 