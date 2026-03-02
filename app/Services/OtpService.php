<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class OtpService
{
    protected $smsService;

    public function __construct(SmsCountryService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Generate and send OTP
     */
    public function sendOtp(User $user, string $type = 'email'): void
    {
        $identifier = $type === 'email' ? $user->email : $user->phone;
        
        // Rate Limiting: 3 requests per 4 hours (14400 seconds)
        $key = 'otp_request:' . $identifier;
        $maxAttempts = 3;
        $decaySeconds = 14400;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $waitDuration = gmdate("H:i:s", $seconds);
            
            // Log the rate limit hit
            Log::warning("OTP rate limit exceeded for {$identifier}. blocked for {$seconds}s");

            abort(429, "Too many OTP requests. Please try again in {$waitDuration}.", [
                'Retry-After' => $seconds
            ]);
        }

        // Cooldown: 120 seconds between requests
        $cooldownKey = 'otp_cooldown:' . $identifier;
        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $seconds = RateLimiter::availableIn($cooldownKey);
            
            abort(429, "Please wait {$seconds} seconds before requesting another OTP.", [
                'Retry-After' => $seconds
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
        RateLimiter::hit($cooldownKey, 120);

        // Invalidate previous OTPs
        Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->update(['is_used' => true]); // Mark as used/invalid

        // Generate 6 digit OTP
        $otpCode = (string) rand(100000, 999999);
        
        // For testing/local, we can log it
        if (config('app.env') !== 'production') {
            Log::info("OTP for {$user->email} ({$type}): {$otpCode}");
        }

        Otp::create([
            'identifier' => $type === 'email' ? $user->email : $user->phone,
            'type' => $type,
            'otp' => Hash::make($otpCode),
            'expires_at' => Carbon::now()->addMinutes(10),
            'is_used' => false,
        ]);

        if ($type === 'email') {
            $this->sendEmailOtp($user, $otpCode);
        } else {
            $this->sendSmsOtp($user, $otpCode);
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(User $user, string $otpCode, string $type = 'email'): bool
    {
        $identifier = $type === 'email' ? $user->email : $user->phone;
        $otpRecord = Otp::where('identifier', $identifier)
            ->where('type', $type)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$otpRecord || !Hash::check($otpCode, $otpRecord->otp)) {
            return false;
        }

        // Mark as verified
        $otpRecord->update(['is_used' => true]);
        
        // Update user verification status
        if ($type === 'email') {
            $user->email_verified_at = now();
            $user->save();
        } elseif ($type === 'phone') {
            // Assuming we have phone_verified_at or similar logic
             $user->forceFill(['phone_verified_at' => now()])->save();
        }

        return true;
    }

    protected function sendEmailOtp(User $user, string $otp)
    {
        try {
            Mail::send('emails.otp', [
                'otp' => $otp,
                'type' => 'email_verification',
                'notifiable' => $user
            ], function ($message) use ($user) {
                $message->to($user->email)
                    ->subject('Your Verification Code');
            });
        } catch (\Exception $e) {
            Log::error("Failed to send Email OTP: " . $e->getMessage());
        }
    }

    protected function sendSmsOtp(User $user, string $otp)
    {
        if (!$user->phone) {
            return;
        }

        try {
            // Use template if available, otherwise raw
            // Assuming template key 'otp_verification' exists
            try {
                $this->smsService->sendTemplate($user->phone, 'dlt_otp_verification', ['otp' => $otp]);
            } catch (\Exception $e) {
                // Fallback to raw message
                $message = "{$otp}. is your verification code for Suganta Tutors. It expires in 5 minutes.";
                $this->smsService->sendRaw($user->phone, $message);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send SMS OTP: " . $e->getMessage());
        }
    }
}
