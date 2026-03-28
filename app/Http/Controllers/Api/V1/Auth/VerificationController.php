<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\AuthService;

class VerificationController extends Controller
{
    use ApiResponse;

    protected $otpService;
    protected AuthService $authService;
    public function __construct(OtpService $otpService , AuthService $authService)
    {
        $this->otpService = $otpService;
        $this->authService = $authService;
    }

    /**
     * Resend verification OTP
     */
    public function resend(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:email,phone',
        ]);

        $user = $request->user();
        $type = $request->type;

        if ($type === 'email' && $user->hasVerifiedEmail()) {
            return $this->error('Email already verified.', 400);
        }

        // Check phone verification status if needed
        // if ($type === 'phone' && $user->phone_verified_at) ...

        $this->otpService->sendOtp($user, $type);

        return $this->success('Verification code sent.');
    }

    /**
     * Verify OTP
     */
    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'email_otp' => 'nullable|string',
            'phone_otp' => 'nullable|string',
        ]);

        if (!$request->email_otp && !$request->phone_otp) {
            return $this->validationError(null, 'Please provide email_otp or phone_otp.');
        }

        $user = $request->user();
        $messages = [];
        $hasError = false;

        // Verify Email OTP
        if ($request->filled('email_otp')) {
            if ($this->otpService->verifyOtp($user, $request->email_otp, 'email')) {
                $messages[] = 'Email verified successfully.';
                $this->authService->logout($request->user());
            } else {
                $messages[] = 'Invalid or expired Email OTP.';
                $hasError = true;
            }
        }

        // Verify Phone OTP
        if ($request->filled('phone_otp')) {
            if ($this->otpService->verifyOtp($user, $request->phone_otp, 'phone')) {
                $messages[] = 'Phone verified successfully.';
                $this->authService->logout($request->user());
            } else {
                $messages[] = 'Invalid or expired Phone OTP.';
                $hasError = true;
            }
        }

        $message = implode(' ', $messages);
        $userData = $user->fresh()->only(['id', 'role', 'email', 'phone', 'email_verified_at', 'registration_fee_status', 'verification_status']);
        $userData['payment_required'] = $user->role != 'student' ? true : false;
        if ($hasError) {
            // In case of error, we might still want to return user data for context?
            // But error() puts data into 'errors'. 
            // Ideally we just return error message.
            return $this->error($message, 400, ['user' => $userData]);
        }
        return $this->success($message, ['user' => $userData]);
    }
}
