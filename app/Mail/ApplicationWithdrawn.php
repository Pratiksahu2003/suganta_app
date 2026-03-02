<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApplicationWithdrawn extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public JobApplication $application;

    public function __construct(JobApplication $application)
    {
        $this->application = $application;
    }

    public function build()
    {
        $subject = 'Application Update: Withdrawn';
        return $this->subject($subject)
            ->view('emails.job-applications.template')
            ->with([
                'subject' => $subject,
                'greeting' => 'Hello ' . ($this->application->applicant_name ?? 'there') . '!',
                'message' => 'Your application has been marked as withdrawn.',
                'extra' => [
                    'Reason' => $this->application->withdrawal_reason,
                ],
            ]);
    }
}


