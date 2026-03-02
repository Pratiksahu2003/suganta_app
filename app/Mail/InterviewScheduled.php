<?php

namespace App\Mail;

use App\Models\JobApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InterviewScheduled extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public JobApplication $application;

    public function __construct(JobApplication $application)
    {
        $this->application = $application;
    }

    public function build()
    {
        $subject = 'Interview Scheduled';
        return $this->subject($subject)
            ->view('emails.job-applications.template')
            ->with([
                'subject' => $subject,
                'greeting' => 'Hello ' . ($this->application->applicant_name ?? 'there') . '!',
                'message' => 'Your interview has been scheduled. Please find the details below.',
                'extra' => [
                    'Interview Date & Time' => optional($this->application->interview_date)->format('M d, Y g:i A'),
                    'Location' => $this->application->interview_location,
                    'Notes' => $this->application->interview_notes,
                ],
            ]);
    }
}


