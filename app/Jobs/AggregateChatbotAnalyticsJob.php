<?php

namespace App\Jobs;

use App\Services\Chatbot\ChatbotAnalyticsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AggregateChatbotAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct()
    {
        $this->onQueue('chatbot');
    }

    public function handle(ChatbotAnalyticsService $analyticsService): void
    {
        try {
            $analyticsService->recalculateUniqueUsersToday();

            Log::info('Chatbot: Daily analytics aggregation completed');
        } catch (\Exception $e) {
            Log::error('Chatbot: Analytics aggregation failed', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
