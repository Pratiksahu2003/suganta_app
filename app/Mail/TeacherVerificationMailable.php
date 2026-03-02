<?php

namespace App\Mail;

use App\Models\User;
use App\Models\TeacherProfile;
use App\Models\Profile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TeacherVerificationMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $teacher;
    public $instituteProfile;
    public $isVerified;
    public $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $teacher, Profile $instituteProfile, bool $isVerified)
    {
        $this->teacher = $teacher;
        $this->instituteProfile = $instituteProfile;
        $this->isVerified = $isVerified;
        $this->loginUrl = route('login');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isVerified 
            ? 'Your Teacher Account Has Been Verified - ' . config('app.name')
            : 'Teacher Verification Status Update - ' . config('app.name');
            
        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-verification',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
