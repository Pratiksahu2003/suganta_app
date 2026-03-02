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

class TeacherUpdateMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $teacher;
    public $instituteProfile;
    public $updatedFields;
    public $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $teacher, Profile $instituteProfile, array $updatedFields)
    {
        $this->teacher = $teacher;
        $this->instituteProfile = $instituteProfile;
        $this->updatedFields = $updatedFields;
        $this->loginUrl = route('login');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Profile Has Been Updated - ' . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-update',
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
