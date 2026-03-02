<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobApplicationReviewed extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public JobApplication $application;

    public function __construct(JobApplication $application)
    {
        $this->application = $application;
    }

    public function build()
    {
        $subject = 'Your application has been reviewed';
        return $this->subject($subject)
            ->view('emails.job-applications.template')
            ->with([
                'subject' => $subject,
                'greeting' => 'Hello ' . ($this->application->applicant_name ?? 'there') . '!',
                'message' => 'Your application has been reviewed. We will keep you updated on the next steps.',
            ]);
    }
}


