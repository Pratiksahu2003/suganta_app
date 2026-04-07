<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Mail\SubscriptionExpiryReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CheckSubscriptionExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired subscriptions and send reminders for upcoming expirations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting subscription expiry check...');

        // 1. Mark expired plans
        $expiredCount = UserSubscription::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);

        $this->info("Marked $expiredCount subscriptions as expired.");

        // 2. Send Reminders (7 Days, 3 Days, 1 Day)
        $reminderDays = [7, 3, 1];

        foreach ($reminderDays as $days) {
            $this->sendReminders($days);
        }

        $this->info('Subscription expiry check completed successfully.');
        return 0;
    }

    /**
     * Send reminders for subscriptions expiring in a specific number of days.
     *
     * @param int $days
     * @return void
     */
    private function sendReminders(int $days)
    {
        $targetDate = now()->addDays($days)->toDateString();
        
        $subscriptions = UserSubscription::with(['user', 'plan'])
            ->where('status', 'active')
            ->whereDate('expires_at', $targetDate)
            ->get();

        $this->info("Found " . $subscriptions->count() . " subscriptions expiring in $days days.");

        foreach ($subscriptions as $subscription) {
            if ($subscription->user && $subscription->user->email) {
                try {
                    Mail::to($subscription->user->email)->send(new SubscriptionExpiryReminder(
                        $subscription->user,
                        $subscription->expires_at,
                        $days,
                        $subscription->plan->name ?? 'Premium'
                    ));
                    
                    $this->line("Sent $days-day reminder to: " . $subscription->user->email);
                } catch (\Exception $e) {
                    Log::error("Failed to send subscription reminder to {$subscription->user->email}: " . $e->getMessage());
                    $this->error("Failed to send reminder to: " . $subscription->user->email);
                }
            }
        }
    }
}
