<?php

namespace App\Jobs;

use App\Models\Chatbot\ChatbotMessage;
use App\Models\Chatbot\ChatbotMessageLog;
use App\Services\Chatbot\MetaGraphApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendChatbotReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 30;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 5;

    public function __construct(
        protected string $platform,
        protected string $recipientId,
        protected int    $messageId,
    ) {
        $this->onQueue('chatbot');
    }

    public function handle(MetaGraphApiService $metaApi): void
    {
        $message = ChatbotMessage::find($this->messageId);

        if (! $message) {
            Log::warning('Chatbot: Message not found for reply job', ['message_id' => $this->messageId]);
            return;
        }

        // Send typing indicator first
        $metaApi->sendTypingIndicator($this->platform, $this->recipientId, true);

        // Small delay to simulate natural typing
        usleep(300000); // 300ms

        // Send the actual message
        $result = $metaApi->sendTextMessage(
            $this->platform,
            $this->recipientId,
            $message->content,
        );

        // Turn off typing
        $metaApi->sendTypingIndicator($this->platform, $this->recipientId, false);

        if ($result['success']) {
            $message->markSent($result['message_id']);

            // Log successful send
            ChatbotMessageLog::create([
                'chatbot_user_id'   => $message->chatbot_user_id,
                'conversation_id'   => $message->conversation_id,
                'platform'          => $this->platform,
                'event_type'        => 'message_sent',
                'payload'           => [
                    'message_id'      => $message->id,
                    'meta_message_id' => $result['message_id'],
                    'matched_by'      => $message->matched_by,
                ],
                'processing_status' => 'success',
            ]);
        } else {
            $message->markFailed();

            Log::error('Chatbot: Failed to send reply via Meta API', [
                'message_id'   => $message->id,
                'recipient_id' => $this->recipientId,
                'platform'     => $this->platform,
                'error'        => $result['error'],
            ]);

            ChatbotMessageLog::create([
                'chatbot_user_id'   => $message->chatbot_user_id,
                'conversation_id'   => $message->conversation_id,
                'platform'          => $this->platform,
                'event_type'        => 'error',
                'payload'           => ['message_id' => $message->id],
                'processing_status' => 'failed',
                'error_message'     => $result['error'],
            ]);

            // Throw to trigger retry
            if ($this->attempts() < $this->tries) {
                throw new \RuntimeException('Meta API send failed: ' . $result['error']);
            }
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Chatbot: SendChatbotReplyJob permanently failed', [
            'message_id'   => $this->messageId,
            'recipient_id' => $this->recipientId,
            'platform'     => $this->platform,
            'error'        => $exception->getMessage(),
        ]);

        $message = ChatbotMessage::find($this->messageId);
        $message?->markFailed();
    }
}
