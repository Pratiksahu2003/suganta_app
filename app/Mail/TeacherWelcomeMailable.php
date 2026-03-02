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

class TeacherWelcomeMailable extends Mailable
{
    use Queueable, SerializesModels;

    public $teacher;
    public $instituteProfile;
    public $password;
    public $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $teacher, Profile $instituteProfile, string $password)
    {
        $this->teacher = $teacher;
        $this->instituteProfile = $instituteProfile;
        $this->password = $password;
        $this->loginUrl = route('login');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to ' . config('app.name') . ' - Your Teacher Account is Ready!',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.teacher-welcome',
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
