<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ModelUpdateSecurityAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public string $modelLabel;
    public string|int|null $modelId;
    public array $changedFields;
    public string $actorName;
    public ?string $ipAddress;
    public ?string $userAgent;
    public string $eventTime;
    /**
     * One of: 'created', 'updated'.
     */
    public string $event;

    /**
     * Tries delivering up to 3 times before marking as failed.
     */
    public int $tries = 3;

    /**
     * Wait 30s before retrying a failed delivery.
     */
    public int $backoff = 30;

    /**
     * Dedicated queue so bulk activity alerts don't clog critical queues.
     *
     * IMPORTANT: The connection is forced to a non-sync driver (default
     * "database") so these emails only go out when a worker picks them up
     * via `php artisan queue:work`. Even if QUEUE_CONNECTION=sync globally,
     * these security alerts stay async.
     */
    public function __construct(
        $user,
        string $modelLabel,
        string|int|null $modelId,
        array $changedFields,
        string $actorName,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $eventTime = null,
        string $event = 'updated'
    ) {
        $this->user = $user;
        $this->modelLabel = $modelLabel;
        $this->modelId = $modelId;
        $this->changedFields = $changedFields;
        $this->actorName = $actorName;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->eventTime = $eventTime ?? now()->format('d M, Y h:i A');
        $this->event = in_array($event, ['created', 'updated'], true) ? $event : 'updated';

        $connection = config('push.model_activity.mail_queue_connection') ?: 'database';
        if ($connection === 'sync') {
            $connection = 'database';
        }

        $this->onConnection($connection);
        $this->onQueue(config('push.model_activity.mail_queue_name', 'default'));
    }

    public function envelope(): Envelope
    {
        $action = $this->event === 'created' ? 'was created' : 'was updated';

        return new Envelope(
            subject: 'Security Alert: ' . $this->modelLabel . ' ' . $action . ' - ' . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.model_update_security_alert',
            with: [
                'user' => $this->user,
                'modelLabel' => $this->modelLabel,
                'modelId' => $this->modelId,
                'changedFields' => $this->changedFields,
                'actorName' => $this->actorName,
                'ipAddress' => $this->ipAddress ?? 'Unknown',
                'userAgent' => $this->userAgent ?? 'Unknown device',
                'eventTime' => $this->eventTime,
                'event' => $this->event,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
