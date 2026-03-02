<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Fluent;

class StudyRequirementOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $email,
        public ?string $name = null
    ) {
    }

    public function build(): self
    {
        $notifiable = new Fluent([
            'name' => $this->name ?? 'User',
            'email' => $this->email,
        ]);

        return $this->subject('Verify your study requirement request')
            ->view('emails.otp')
            ->with([
                'otp' => $this->otp,
                'type' => 'study_requirement',
                'notifiable' => $notifiable,
            ]);
    }
}

