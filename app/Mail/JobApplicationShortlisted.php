<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobApplicationShortlisted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public JobApplication $application;

    public function __construct(JobApplication $application)
    {
        $this->application = $application;
    }

    public function build()
    {
        $subject = 'You have been shortlisted';
        return $this->subject($subject)
            ->view('emails.job-applications.template')
            ->with([
                'subject' => $subject,
                'greeting' => 'Congratulations ' . ($this->application->applicant_name ?? 'there') . '!',
                'message' => 'Great news! You have been shortlisted for the next stage. We will contact you with further details.',
            ]);
    }
}


