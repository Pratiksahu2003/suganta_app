<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthService
{
    protected SessionService $sessionService;
    protected UserActivityNotificationService $userActivityService;
    protected PasswordNotificationService $passwordNotificationService;
    protected RegistrationPaymentService $registrationPaymentService;
    protected OtpService $otpService;

    public function __construct(
        SessionService $sessionService,
        UserActivityNotificationService $userActivityService,
        PasswordNotificationService $passwordNotificationService,
        RegistrationPaymentService $registrationPaymentService,
        OtpService $otpService
    ) {
        $this->sessionService = $sessionService;
        $this->userActivityService = $userActivityService;
        $this->passwordNotificationService = $passwordNotificationService;
        $this->registrationPaymentService = $registrationPaymentService;
        $this->otpService = $otpService;
    }

    /**
     * Register a new user
     */
    public function register(array $data, Request $request): array
    {
        return DB::transaction(function () use ($data, $request) {
            try {
                // Create the user
                $user = User::create([
                    'name' => $data['first_name'] . ' ' . $data['last_name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'role' => $data['role'],
                    'phone' => $data['phone'] ?? null,
                    'is_active' => true,
                    'verification_status' => 'pending',
                    'referred_by' => $data['referral_code'] ?? null,
                    'preferences' => [
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                    ],
                ]);

                // Assign role
                $this->assignUserRole($user, $data['role']);

                // Create token
                $deviceName = $data['device_name'] ?? ($request->userAgent() ?? 'Unknown Device');
                $token = $user->createToken($deviceName)->plainTextToken;

                // Create Profile
                Profile::create([
                    'user_id' => $user->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'display_name' => $data['first_name'] . ' ' . $data['last_name'],
                ]);

                // Create session record (Fail gracefully if session creation fails)
                try {
                    $this->sessionService->createSession($user, $request);
                } catch (\Exception $e) {
                    Log::error('Session creation failed during registration: ' . $e->getMessage());
                    // We continue even if session creation fails, as it's not critical for account creation
                }

                // Send registration notification (Fail gracefully)
                try {
                    $this->userActivityService->loginSuccessful($user, [
                        'login_method' => 'api_registration'
                    ]);
                } catch (\Exception $e) {
                    Log::error('Activity logging failed during registration: ' . $e->getMessage());
                }

                // Send OTP (Fail gracefully but log error)
                try {
                    $this->otpService->sendOtp($user, 'email');
                    if ($user->phone) {
                        $this->otpService->sendOtp($user, 'phone');
                    }
                } catch (\Exception $e) {
                    Log::error('OTP sending failed during registration: ' . $e->getMessage());
                }

                return [
                    'user' => $user->only(['id', 'name', 'email', 'role' ,'email_verified_at', 'phone_verified_at' ,'registration_fee_status']),
                    'token' => $token,
                    'token_type' => 'Bearer'
                ];
            } catch (\Exception $e) {
                Log::error('AuthService Registration failed: ' . $e->getMessage(), [
                    'email' => $data['email'] ?? 'unknown',
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e; // Transaction will rollback
            }
        });
    }

    /**
     * Login user
     */
    public function login(array $credentials, Request $request): array
    {
        try {
            if (!Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) {
                $user = User::where('email', $credentials['email'])->first();
                if ($user) {
                    $this->userActivityService->loginFailed($user, 'Invalid password via API');
                }
                throw ValidationException::withMessages([
                    'email' => ['Invalid credentials'],
                ]);
            }

            $user = Auth::user();

            if (!$user->is_active) {
                throw new \Exception('Account is deactivated', 403);
            }

            if ($user->email_verified_at === null) {
                throw new \Exception('Email not verified. Please verify your email before logging in.', 403);
            }

            // Check registration fee
            $registrationStatus = $user->registration_fee_status ?? null;
            if ($registrationStatus !== 'paid' && $registrationStatus !== 'not_required' && $user->role != 'student') {
                $paymentResult = $this->registrationPaymentService->getOrCreateCheckoutUrl($user, 'api');

                if (!empty($paymentResult['checkout_url'])) {
                    return [
                        'requires_registration_payment' => true,
                        'payment_link' => $paymentResult['checkout_url'],
                        'order_id' => $paymentResult['order_id'] ?? null,
                        'actual_price' => $paymentResult['actual_price'] ?? null,
                        'discounted_price' => $paymentResult['discounted_price'] ?? null,
                        'description' => $paymentResult['description'] ?? null,
                        'role' => $user->role,
                        'message' => 'Registration fee payment is required to complete login.'
                    ];
                }

                if (!($paymentResult['success'] ?? false) && !empty($paymentResult['message'])) {
                    throw new \Exception($paymentResult['message'], 403);
                }
            }

            $deviceName = $credentials['device_name'] ?? $request->ip();
            $token = $user->createToken($deviceName)->plainTextToken;

            $this->sessionService->createSession($user, $request);
            $this->checkUnusualLoginLocation($user);
            $this->checkNewDevice($user);

            $this->userActivityService->loginSuccessful($user, [
                'login_method' => 'api_email_password',
                'device_name' => $deviceName
            ]);

            $responseData = [
                'user' => $user->only(['id', 'name', 'email', 'role']),
                'email_verified_at' => $user->email_verified_at,
                'registration_fee_status' => in_array($user->registration_fee_status, ['paid', 'not_required']),
                'token' => $token,
                'token_type' => 'Bearer'
            ];

            // Add phone_verified_at if it exists
            if ($user->phone) {
                $responseData['phone_verified_at'] = $user->phone_verified_at ?? null;
            }

            return $responseData;
        } catch (\Exception $e) {
            Log::error('AuthService Login failed: ' . $e->getMessage(), [
                'email' => $credentials['email'] ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Logout user
     */
    public function logout(User $user): void
    {
        try {
            $currentSession = $this->sessionService->getCurrentSession($user);
            if ($currentSession) {
                $this->sessionService->deactivateSession($currentSession);
            }

            $user->currentAccessToken()->delete();
            $this->userActivityService->logout($user);
        } catch (\Exception $e) {
            Log::error('AuthService Logout failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Logout from all devices
     */
    public function logoutFromAllDevices(User $user): void
    {
        try {
            $this->sessionService->deactivateUserSessions($user);
            $user->tokens()->delete();
        } catch (\Exception $e) {
            Log::error('AuthService LogoutAll failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Refresh token
     */
    public function refreshToken(User $user): string
    {
        try {
            $user->currentAccessToken()->delete();
            return $user->createToken('auth-token')->plainTextToken;
        } catch (\Exception $e) {
            Log::error('AuthService RefreshToken failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Forgot password
     */
    public function forgotPassword(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return; // Return silently
        }

        if (!$user->is_active) {
            throw new \Exception('Account is deactivated', 403);
        }

        $user->sendPasswordResetNotification(
            Password::createToken($user)
        );

        Log::info('Password reset requested for user: ' . $user->id, [
            'email' => $email
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword(array $data): string
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
             throw new \Exception('User not found', 404);
        }

        if (!$user->is_active) {
            throw new \Exception('Account is deactivated', 403);
        }

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();
        });

        if ($status === Password::PASSWORD_RESET) {
            Log::info('Password reset successful for user: ' . $user->id, [
                'email' => $data['email']
            ]);

            $this->passwordNotificationService->passwordReset($user, [
                'reset_method' => 'api_reset'
            ]);

            $user->tokens()->delete();
        }

        return $status;
    }

    private function assignUserRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            $user->roles()->attach($role->id);
            if ($user->role !== $roleName) {
                $user->update(['role' => $roleName]);
            }
            Log::info("Role '{$roleName}' assigned to user {$user->id} successfully");
        } else {
            Log::error("Role '{$roleName}' not found when assigning to user {$user->id}");
        }
    }

    private function checkUnusualLoginLocation(User $user): void
    {
        // Implementation for location checking
    }

    private function checkNewDevice(User $user): void
    {
        // Implementation for device checking
    }
}
