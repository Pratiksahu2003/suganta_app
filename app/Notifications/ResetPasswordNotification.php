<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $frontendUrl = rtrim(config('app.frontend_url', 'https://www.suganta.com'), '/');
        $email = $notifiable->getEmailForPasswordReset() ?? $notifiable->email;

        $url = "{$frontendUrl}/reset-password/{$this->token}?email=" . urlencode($email);

        return (new MailMessage)
            ->subject('Reset Your Password - ' . config('company.name', config('app.name')))
            ->view('emails.reset-password', [
                'url' => $url,
                'notifiable' => $notifiable,
            ]);
    }
}
