<?php

namespace App\Console\Commands;

use App\Mail\RegistrationPaymentReminder;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class CheckPendingRegistrationPayments extends Command
{
    private const REMINDER_TTL_SECONDS = 604800; // 7 days

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registration:check-pending-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders to users with pending registration payment older than 24 hours';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting pending registration payment check...');

        $requiredRoles = data_get(config('registration', []), 'payment.required_for_roles', []);
        if (!is_array($requiredRoles) || empty($requiredRoles)) {
            $this->warn('No roles configured for registration payment reminders.');
            return 0;
        }

        $this->cleanupReminderKeysForCompletedPayments($requiredRoles);

        $users = User::query()
            ->whereNotNull('email')
            ->whereIn('role', $requiredRoles)
            ->whereIn('registration_fee_status', ['pending', ''])
            ->where('created_at', '<=', now()->subDay())
            ->get();

        $this->info('Found ' . $users->count() . ' users with pending registration payment.');

        foreach ($users as $user) {
            if ($this->wasRemindedRecently($user)) {
                $this->line('Skipped (already reminded this week): ' . $user->email);
                continue;
            }

            try {
                Mail::to($user->email)->queue(new RegistrationPaymentReminder($user));
                $this->markReminded($user);
                $this->line('Sent payment reminder to: ' . $user->email);
            } catch (\Exception $e) {
                Log::error('Failed to send registration payment reminder', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
                $this->error('Failed to send reminder to: ' . $user->email);
            }
        }

        $this->info('Pending registration payment check completed.');
        return 0;
    }

    private function wasRemindedRecently(User $user): bool
    {
        $key = $this->getReminderRedisKey($user);

        try {
            return (bool) Redis::exists($key);
        } catch (\Exception $e) {
            Log::warning('Redis check failed for registration reminder key', [
                'user_id' => $user->id,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);

            // If Redis is down, do not block sending reminders.
            return false;
        }
    }

    private function markReminded(User $user): void
    {
        $key = $this->getReminderRedisKey($user);

        try {
            Redis::setex($key, self::REMINDER_TTL_SECONDS, now()->toDateTimeString());
        } catch (\Exception $e) {
            Log::warning('Failed to store registration reminder key in Redis', [
                'user_id' => $user->id,
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getReminderRedisKey(User $user): string
    {
        return 'registration:payment-reminder:sent:user:' . $user->id;
    }

    private function cleanupReminderKeysForCompletedPayments(array $requiredRoles): void
    {
        $paidUsers = User::query()
            ->select('id', 'email')
            ->whereIn('role', $requiredRoles)
            ->whereIn('registration_fee_status', ['paid', 'not_required'])
            ->whereNotNull('id')
            ->get();

        foreach ($paidUsers as $user) {
            $key = $this->getReminderRedisKey($user);

            try {
                Redis::del($key);
            } catch (\Exception $e) {
                Log::warning('Failed to delete registration reminder key in Redis', [
                    'user_id' => $user->id,
                    'key' => $key,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
