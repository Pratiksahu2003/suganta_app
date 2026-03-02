<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class JobOffered extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public JobApplication $application;

    public function __construct(JobApplication $application)
    {
        $this->application = $application;
    }

    public function build()
    {
        $subject = 'Job Offer';
        return $this->subject($subject)
            ->view('emails.job-applications.template')
            ->with([
                'subject' => $subject,
                'greeting' => 'Congratulations ' . ($this->application->applicant_name ?? 'there') . '!',
                'message' => 'You have been offered the role. Please check your dashboard or contact us for next steps.',
            ]);
    }
}


