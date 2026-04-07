<?php

namespace App\Jobs;

use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendSystemNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    protected int $userId;

    /**
     * @var string
     */
    protected string $title;

    /**
     * @var string
     */
    protected string $message;

    /**
     * @var string
     */
    protected string $type;

    /**
     * @var array
     */
    protected array $data;

    /**
     * @var string|null
     */
    protected ?string $actionUrl;

    /**
     * @var string
     */
    protected string $priority;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(
        int $userId,
        string $title,
        string $message,
        string $type = 'general',
        array $data = [],
        ?string $actionUrl = null,
        string $priority = 'normal'
    ) {
        $this->userId = $userId;
        $this->title = $title;
        $this->message = $message;
        $this->type = $type;
        $this->data = $data;
        $this->actionUrl = $actionUrl;
        $this->priority = $priority;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(NotificationService $notificationService)
    {
        $notificationService->createUserNotification(
            $this->userId,
            $this->title,
            $this->message,
            $this->type,
            $this->data,
            $this->actionUrl,
            $this->priority
        );
    }
}
