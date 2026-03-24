<?php

namespace App\Console\Commands;

use App\Models\GoogleWatchChannel;
use App\Models\User;
use App\Services\V4\Google\GoogleTokenService;
use App\Services\V4\Google\GoogleWatchService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GoogleRenewWatchesCommand extends Command
{
    protected $signature = 'google:watches-renew {--dry-run : Preview channels only}';

    protected $description = 'Renew expiring Google watch channels before expiry.';

    public function __construct(
        private readonly GoogleWatchService $googleWatchService,
        private readonly GoogleTokenService $googleTokenService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $renewed = 0;
        $failed = 0;
        $total = 0;

        $this->googleWatchService->expiringChannels()->chunkById(100, function ($channels) use (&$renewed, &$failed, &$total, $isDryRun): void {
            /** @var GoogleWatchChannel $channel */
            foreach ($channels as $channel) {
                $total++;
                if ($isDryRun) {
                    $this->line("Would renew channel {$channel->channel_id} ({$channel->resource_type})");
                    continue;
                }

                try {
                    /** @var User|null $user */
                    $user = $channel->user()->first();
                    if (! $user) {
                        $failed++;
                        continue;
                    }

                    $accessToken = $this->googleTokenService->getValidAccessToken($user);
                    $newChannel = $this->googleWatchService->renewChannel($channel, $accessToken);
                    $renewed++;

                    $this->line("Renewed {$channel->channel_id} => {$newChannel->channel_id}");
                } catch (\Throwable $exception) {
                    $failed++;
                    Log::warning('Google watch renewal failed.', [
                        'channel_id' => $channel->channel_id,
                        'user_id' => $channel->user_id,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }
        });

        $this->info("Google watch renewal done. total={$total}, renewed={$renewed}, failed={$failed}, dry_run=".($isDryRun ? 'yes' : 'no'));

        return self::SUCCESS;
    }
}
