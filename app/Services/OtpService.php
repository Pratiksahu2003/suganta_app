<?php

namespace App\Services;

use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
        // Invalidate previous OTPs
        Otp::where('user_id', $user->id)
            ->where('type', $type)
            ->update(['verified' => true]); // Mark as used/invalid

        // Generate 6 digit OTP
        $otpCode = (string) rand(100000, 999999);
        
        // For testing/local, we can log it
        if (config('app.env') !== 'production') {
            Log::info("OTP for {$user->email} ({$type}): {$otpCode}");
        }

        Otp::create([
            'user_id' => $user->id,
            'identifier' => $type === 'email' ? $user->email : $user->phone,
            'type' => $type,
            'otp' => Hash::make($otpCode),
            'expires_at' => Carbon::now()->addMinutes(10),
            'verified' => false,
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
        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', $type)
            ->where('verified', false)
            ->where('expires_at', '>', Carbon::now())
            ->latest()
            ->first();

        if (!$otpRecord || !Hash::check($otpCode, $otpRecord->otp)) {
            return false;
        }

        // Mark as verified
        $otpRecord->update(['verified' => true]);
        
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
        // Simple raw email for now to avoid creating Mailable class complexity if not needed
        // Or use the existing notification service if it supports OTP
        try {
             Mail::raw("Your OTP is: {$otp}. It expires in 10 minutes.", function ($message) use ($user) {
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
                $this->smsService->sendTemplate($user->phone, 'otp_verification', ['otp' => $otp]);
            } catch (\Exception $e) {
                // Fallback to raw message
                $message = "Your Verification Code is {$otp}. Valid for 10 minutes.";
                $this->smsService->sendRaw($user->phone, $message);
            }
        } catch (\Exception $e) {
            Log::error("Failed to send SMS OTP: " . $e->getMessage());
        }
    }
}
