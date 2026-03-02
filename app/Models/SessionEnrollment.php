<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class SessionEnrollment extends Model
{
	protected $table = 'session_enrollments';

	protected $fillable = [
		'session_id',
		'student_id',
		'payment_id',
		'enrolled_at',
		'status',
		'payment_status',
		'amount_paid',
		'rating',
		'review',
	];

	protected $casts = [
		'enrolled_at' => 'datetime',
		'amount_paid' => 'float',
		'rating' => 'float',
		'created_at' => 'datetime',
		'updated_at' => 'datetime',
	];
	public function student()
	{
		return $this->belongsTo(User::class, 'student_id');
	}

	public function profile()
	{
		return $this->hasOneThrough(
			Profile::class,    // Final model
			User::class,       // Intermediate model
			'id',              // Foreign key on User table...
			'user_id',         // Foreign key on Profile table...
			'student_id',      // Local key on SessionEnrollment table...
			'id'               // Local key on User table...
		);
	}

    public function session()
    {
        return $this->belongsTo(TeacherSession::class, 'session_id');
    }

    /**
     * Get the payment associated with this enrollment
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}
