<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Mail\SubscriptionExpiryReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\NotificationService;
use App\Jobs\SendSystemNotification;

class CheckSubscriptionExpiry extends Command
{
    public function __construct()
    {
        parent::__construct();
    }
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

        // 1. Mark expired plans and notify
        $expiredSubscriptions = UserSubscription::where('status', 'active')
            ->where('expires_at', '<', now())
            ->with(['user', 'plan'])
            ->get();

        /** @var \App\Models\UserSubscription $subscription */
        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);

            if ($subscription->user) {
                try {
                    SendSystemNotification::dispatch(
                        $subscription->user_id,
                        'Subscription Expired',
                        "Your '{$subscription->plan->name}' subscription has expired. Renew now to maintain access.",
                        'subscription_expired',
                        ['plan_name' => $subscription->plan->name ?? 'Premium'],
                        null, // Add renewal URL if available
                        'high'
                    );
                } catch (\Exception $e) {
                    Log::error("Failed to send expiry notification to user {$subscription->user_id}: " . $e->getMessage());
                }
            }
        }

        $this->info("Marked " . $expiredSubscriptions->count() . " subscriptions as expired.");

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

        /** @var \App\Models\UserSubscription $subscription */
        foreach ($subscriptions as $subscription) {
            if ($subscription->user) {
                // Prepare notification content
                $planName = $subscription->plan->name ?? 'Premium';
                $title = "Subscription Expiration Reminder";
                $message = "Your $planName subscription will expire in $days day(s). Renew now to avoid interruption.";

                // Send Push Notification
                try {
                    SendSystemNotification::dispatch(
                        $subscription->user_id,
                        $title,
                        $message,
                        'subscription_reminder',
                        [
                            'days_left' => $days,
                            'plan_name' => $planName,
                            'expires_at' => $subscription->expires_at->toDateTimeString()
                        ],
                        null, // renewal URL
                        'high'
                    );
                    $this->line("Sent $days-day push notification to user: " . $subscription->user_id);
                } catch (\Exception $e) {
                    Log::error("Failed to send push reminder to user {$subscription->user_id}: " . $e->getMessage());
                }

                // Send Email
                if ($subscription->user->email) {
                    try {
                        Mail::to($subscription->user->email)->queue(new SubscriptionExpiryReminder(
                            $subscription->user,
                            $subscription->expires_at,
                            $days,
                            $planName
                        ));
                        
                        $this->line("Sent $days-day email reminder to: " . $subscription->user->email);
                    } catch (\Exception $e) {
                        Log::error("Failed to send email reminder to {$subscription->user->email}: " . $e->getMessage());
                        $this->error("Failed to send email reminder to: " . $subscription->user->email);
                    }
                }
            }
        }
    }
}
