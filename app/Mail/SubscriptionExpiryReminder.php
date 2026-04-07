<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SubscriptionExpiryReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $expiry_date;
    public $days_remaining;
    public $plan_name;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user, $expiry_date, $days_remaining, $plan_name)
    {
        $this->user = $user;
        $this->expiry_date = $expiry_date;
        $this->days_remaining = $days_remaining;
        $this->plan_name = $plan_name;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Action Required: Your ' . config('app.name') . ' Plan is Expiring')
                    ->view('emails.subscription_reminder');
    }
}
