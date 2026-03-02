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
            'otp' => 'required|string',
            'type' => 'required|in:email,phone',
        ]);

        $user = $request->user();
        
        if ($this->otpService->verifyOtp($user, $request->otp, $request->type)) {
            return response()->json(['message' => ucfirst($request->type) . ' verified successfully.']);
        }

        return response()->json(['message' => 'Invalid or expired verification code.'], 400);
    }
}
