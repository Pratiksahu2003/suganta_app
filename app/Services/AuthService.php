<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Profile;
use App\Services\InputDetectionService;
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
    protected InputDetectionService $inputDetectionService;

    public function __construct(
        SessionService $sessionService,
        UserActivityNotificationService $userActivityService,
        PasswordNotificationService $passwordNotificationService,
        RegistrationPaymentService $registrationPaymentService,
        OtpService $otpService,
        InputDetectionService $inputDetectionService
    ) {
        $this->sessionService = $sessionService;
        $this->userActivityService = $userActivityService;
        $this->passwordNotificationService = $passwordNotificationService;
        $this->registrationPaymentService = $registrationPaymentService;
        $this->otpService = $otpService;
        $this->inputDetectionService = $inputDetectionService;
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
            $identifier = $credentials['email'];
            $type = $this->inputDetectionService->detectType($identifier);

            if (!$type) {
                throw ValidationException::withMessages([
                    'email' => ['Invalid email or phone number format'],
                ]);
            }

            $user = null;
            if ($type === 'email') {
                $user = User::where('email', $identifier)->first();
            } elseif ($type === 'phone') {
                // Ensure phone format matches DB storage (assuming E.164 or normalized)
                // For now, we try exact match or simplified match
                $user = User::where('phone', $identifier)->first();
            }

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                if ($user) {
                    $this->userActivityService->loginFailed($user, 'Invalid password via API');
                }
                throw ValidationException::withMessages([
                    'email' => ['Invalid credentials'],
                ]);
            }

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

            // Trusted Device Check
            $deviceToken = $request->header('X-Device-Token');
            $isTrusted = false;
            if ($deviceToken) {
                // Check cache for trusted device token
                $cacheKey = 'trusted_device:' . $user->id . ':' . $deviceToken;
                if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                    $isTrusted = true;
                }
            }

            // If not trusted, require OTP (unless disabled via config, but requirement says "After password verification, trigger OTP")
            if (!$isTrusted) {
                // Send OTP
                // Use detected type as preference, or fallback to email if phone not available
                $otpType = $type;
                if ($type === 'phone' && empty($user->phone)) {
                    $otpType = 'email';
                }
                
                $this->otpService->sendOtp($user, $otpType);

                return [
                    'requires_otp' => true,
                    'identifier' => $identifier,
                    'type' => $otpType, // Tell frontend which OTP to expect
                    'message' => "OTP sent to your {$otpType}. Please verify to complete login."
                ];
            }

            // If Trusted, proceed to login
            $deviceName = $credentials['device_name'] ?? $request->ip();
            $token = $user->createToken($deviceName)->plainTextToken;

            $this->sessionService->createSession($user, $request);
            $this->checkUnusualLoginLocation($user);
            $this->checkNewDevice($user);

            $this->userActivityService->loginSuccessful($user, [
                'login_method' => 'api_email_password_trusted',
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

    public function sendLoginOtp(string $identifier): array
    {
        $type = $this->inputDetectionService->detectType($identifier);

        if (!$type) {
             throw ValidationException::withMessages([
                'identifier' => ['Invalid identifier format'],
            ]);
        }

        $user = null;
        if ($type === 'email') {
            $user = User::where('email', $identifier)->first();
        } elseif ($type === 'phone') {
            $user = User::where('phone', $identifier)->first();
        }

        if (!$user) {
             throw new \Exception('User not found', 404);
        }

        if (!$user->is_active) {
            throw new \Exception('Account is deactivated', 403);
        }

        // Send OTP
        $this->otpService->sendOtp($user, $type);

        return [
            'success' => true,
            'message' => "OTP sent to your {$type}.",
            'identifier' => $identifier,
            'type' => $type
        ];
    }

    /**
     * Verify Login OTP and issue token
     */
    public function verifyLogin(array $data, Request $request): array
    {
        $identifier = $data['identifier'];
        $otp = $data['otp'];
        $type = $this->inputDetectionService->detectType($identifier);

        if (!$type) {
             throw ValidationException::withMessages([
                'identifier' => ['Invalid identifier format'],
            ]);
        }

        $user = null;
        if ($type === 'email') {
            $user = User::where('email', $identifier)->first();
        } elseif ($type === 'phone') {
            $user = User::where('phone', $identifier)->first();
        }

        if (!$user) {
             throw ValidationException::withMessages([
                'identifier' => ['User not found'],
            ]);
        }

        // Verify OTP
        // Note: verifyOtp checks expiry, usage, and increments attempts
        if (!$this->otpService->verifyOtp($user, $otp, $type)) {
             throw ValidationException::withMessages([
                'otp' => ['Invalid or expired OTP'],
            ]);
        }
        
        // Check active status after verification to prevent enumeration?
        // Or before? verifyOtp doesn't check active.
        if (!$user->is_active) {
            throw new \Exception('Account is deactivated', 403);
        }

        // Login Successful
        $deviceName = $data['device_name'] ?? $request->ip();
        $token = $user->createToken($deviceName)->plainTextToken;

        $this->sessionService->createSession($user, $request);
        $this->checkUnusualLoginLocation($user);
        $this->checkNewDevice($user);

        // Handle Remember Device
        $deviceToken = null;
        if (!empty($data['remember_device'])) {
            $deviceToken = \Illuminate\Support\Str::random(64);
            $cacheKey = 'trusted_device:' . $user->id . ':' . $deviceToken;
            // Store for 30 days
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(30));
        }

        $this->userActivityService->loginSuccessful($user, [
            'login_method' => 'api_otp_verification',
            'device_name' => $deviceName
        ]);

        $responseData = [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user->only(['id', 'name', 'email', 'role']),
            'token' => $token,
            'token_type' => 'Bearer',
        ];

        if ($deviceToken) {
            $responseData['device_token'] = $deviceToken;
        }

        return $responseData;
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
