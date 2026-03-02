<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationRejected extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public JobApplication $application;

    public function __construct(JobApplication $application)
    {
        $this->application = $application;
    }

    public function build()
    {
        $subject = 'Application Update: Rejected';
        return $this->subject($subject)
            ->view('emails.job-applications.template')
            ->with([
                'subject' => $subject,
                'greeting' => 'Hello ' . ($this->application->applicant_name ?? 'there') . '!',
                'message' => 'After careful review, we will not be moving forward at this time.',
                'extra' => [
                    'Reason' => $this->application->rejection_reason,
                ],
            ]);
    }
}


