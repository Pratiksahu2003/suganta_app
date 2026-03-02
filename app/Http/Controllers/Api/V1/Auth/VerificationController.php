<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VerificationController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    /**
     * Resend verification OTP
     */
    public function resend(Request $request)
    {
        $request->validate([
            'type' => 'required|in:email,phone',
        ]);

        $user = $request->user();
        $type = $request->type;

        if ($type === 'email' && $user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email already verified.'], 400);
        }

        // Check phone verification status if needed
        // if ($type === 'phone' && $user->phone_verified_at) ...

        $this->otpService->sendOtp($user, $type);

        return response()->json(['message' => 'Verification code sent.']);
    }

    /**
     * Verify OTP
     */
    public function verify(Request $request)
    {
        $request->validate([
            'email_otp' => 'nullable|string',
            'phone_otp' => 'nullable|string',
        ]);

        if (!$request->email_otp && !$request->phone_otp) {
            return response()->json(['message' => 'Please provide email_otp or phone_otp.'], 422);
        }

        $user = $request->user();
        $messages = [];
        $hasError = false;

        // Verify Email OTP
        if ($request->filled('email_otp')) {
            if ($this->otpService->verifyOtp($user, $request->email_otp, 'email')) {
                $messages[] = 'Email verified successfully.';
            } else {
                $messages[] = 'Invalid or expired Email OTP.';
                $hasError = true;
            }
        }

        // Verify Phone OTP
        if ($request->filled('phone_otp')) {
            if ($this->otpService->verifyOtp($user, $request->phone_otp, 'phone')) {
                $messages[] = 'Phone verified successfully.';
            } else {
                $messages[] = 'Invalid or expired Phone OTP.';
                $hasError = true;
            }
        }

        return response()->json([
            'message' => implode(' ', $messages),
            'user' => $user->fresh()->only(['id', 'email', 'phone', 'email_verified_at', 'phone_verified_at', 'verification_status'])
        ], $hasError ? 400 : 200);
    }
}
