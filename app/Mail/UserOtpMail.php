<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public User $user,
        public string $otp,
        public string $type = 'email_verification'
    ) {
    }

    public function build(): self
    {
        return $this->subject('Your Verification Code')
            ->view('emails.otp')
            ->with([
                'otp' => $this->otp,
                'type' => $this->type,
                'notifiable' => $this->user,
            ]);
    }
}
