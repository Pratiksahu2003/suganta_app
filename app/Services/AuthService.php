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
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    // Constants
    private const TRUSTED_DEVICE_CACHE_DAYS = 30;
    private const TRUSTED_DEVICE_TOKEN_LENGTH = 64;
    private const DEFAULT_DEVICE_NAME = 'Unknown Device';
    private const TOKEN_TYPE = 'Bearer';
    
    // Login methods
    private const LOGIN_METHOD_REGISTRATION = 'api_registration';
    private const LOGIN_METHOD_TRUSTED = 'api_email_password_trusted';
    private const LOGIN_METHOD_OTP = 'api_otp_verification';
    
    // Registration fee statuses
    private const FEE_STATUS_PAID = 'paid';
    private const FEE_STATUS_NOT_REQUIRED = 'not_required';
    
    // User roles
    private const ROLE_STUDENT = 'student';
    
    // Verification statuses
    private const VERIFICATION_PENDING = 'pending';
    
    // Error messages
    private const ERROR_INVALID_CREDENTIALS = 'Invalid credentials';
    private const ERROR_ACCOUNT_DEACTIVATED = 'Account is deactivated';
    private const ERROR_EMAIL_NOT_VERIFIED = 'Email not verified. Please verify your email before logging in.';
    private const ERROR_USER_NOT_FOUND = 'User not found';
    private const ERROR_INVALID_OTP = 'Invalid or expired OTP';
    private const ERROR_INVALID_FORMAT = 'Invalid email or phone number format';
    private const ERROR_INVALID_PHONE_FORMAT = 'Invalid phone number format';
    private const ERROR_INVALID_IDENTIFIER_FORMAT = 'Invalid identifier format';
    
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
     * Register a new user with comprehensive validation and setup
     * 
     * @param array $data Registration data containing user information
     * @param Request $request HTTP request object for session tracking
     * @return array Registration response with user data and token
     * @throws \Exception When registration fails
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
                    'verification_status' => self::VERIFICATION_PENDING,
                    'email_verified_at' => now()->toDateTimeString(), // Assuming email is verified at registration for now
                    'referred_by' => $data['referral_code'] ?? null,
                    'preferences' => [
                        'first_name' => $data['first_name'],
                        'last_name' => $data['last_name'],
                    ],
                ]);

                // Assign role
                $this->assignUserRole($user, $data['role']);

                $deviceName = $data['device_name'] ?? ($request->userAgent() ?? self::DEFAULT_DEVICE_NAME);
                $token = $user->createToken($deviceName)->plainTextToken;
                if ($this->isStatefulSPARequest($request)) {
                    $this->loginWebSession($user, $request);
                }

                // Create Profile
                Profile::create([
                    'user_id' => $user->id,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'display_name' => $data['first_name'] . ' ' . $data['last_name'],
                ]);

                // Handle optional registration tasks gracefully
                $this->executeGracefully(
                    fn() => $this->sessionService->createSession($user, $request),
                    'Session creation failed during registration'
                );

                $this->executeGracefully(
                    fn() => $this->userActivityService->loginSuccessful($user, [
                        'login_method' => self::LOGIN_METHOD_REGISTRATION
                    ]),
                    'Activity logging failed during registration'
                );

                // $this->executeGracefully(function() use ($user) {
                //     $this->otpService->sendOtp($user, 'email');
                // }, 'OTP sending failed during registration');

                $requiresPayment = in_array($user->role, config('registration.payment.required_for_roles', []), true);
                $registrationCharges = $requiresPayment
                    ? config("registration.charges.{$user->role}")
                    : null;

                return $this->buildRegistrationResponse($user, $token, $requiresPayment, $registrationCharges, $request);
            } catch (\Exception $e) {
                $this->handleAuthError($e, 'Registration', [
                    'email' => $data['email'] ?? 'unknown'
                ]);
                throw $e; // Transaction will rollback
            }
        });
    }

    /**
     * Authenticate user login with multi-factor authentication support
     * 
     * @param array $credentials Login credentials (email/phone and password)
     * @param Request $request HTTP request object for device tracking
     * @return array Login response with user data, token, or payment/OTP requirements
     * @throws ValidationException When credentials are invalid
     * @throws \Exception When account is deactivated or other login issues occur
     */
    public function login(array $credentials, Request $request): array
    {
        try {
            $identifier = $credentials['email'];
            $type = $this->inputDetectionService->detectType($identifier);

            if (!$type) {
                throw ValidationException::withMessages([
                    'email' => [self::ERROR_INVALID_FORMAT],
                ]);
            }

            $user = $this->findUserByIdentifier($identifier);

            if (!$user || !Hash::check($credentials['password'], $user->password)) {
                if ($user) {
                    $this->userActivityService->loginFailed($user, 'Invalid password via API');
                }
                throw ValidationException::withMessages([
                    'email' => [self::ERROR_INVALID_CREDENTIALS],
                ]);
            }

            $this->validateUserAccount($user);
            $this->validateEmailVerification($user);

            // Check registration payment requirements
            $paymentResponse = $this->checkRegistrationPayment($user);
            if ($paymentResponse) {
                return $paymentResponse;
            }

            // Check if device is trusted
            if (!$this->isTrustedDevice($user, $request)) {
                return $this->handleOtpRequirement($user, $identifier, $type);
            }

            // Complete trusted device login
            return $this->completeTrustedLogin($user, $credentials, $request);
            
        } catch (\Exception $e) {
            $this->handleAuthError($e, 'Login', [
                'email' => $credentials['email'] ?? 'unknown'
            ]);
            throw $e;
        }
    }

    /**
     * Send OTP for login verification
     * 
     * @param string $identifier User email or phone number
     * @return array Success response with OTP details
     * @throws ValidationException When identifier format is invalid
     * @throws \Exception When user not found or account deactivated
     */
    public function sendLoginOtp(string $identifier): array
    {
        $type = $this->inputDetectionService->detectType($identifier);

        if (!$type) {
            throw ValidationException::withMessages([
                'identifier' => [self::ERROR_INVALID_IDENTIFIER_FORMAT],
            ]);
        }

        $user = $this->getUserByIdentifier($identifier);
        $this->validateUserAccount($user);

        $this->otpService->sendOtp($user, $type);

        return [
            'success' => true,
            'message' => "OTP sent to your {$type}.",
            'identifier' => $identifier,
            'type' => $type
        ];
    }

    /**
     * Verify Login OTP and complete authentication process
     * 
     * @param array $data OTP verification data (identifier, otp, device_name, remember_device)
     * @param Request $request HTTP request object for session tracking
     * @return array Login response with user data, token, or payment requirements
     * @throws ValidationException When OTP is invalid or identifier format is wrong
     * @throws \Exception When account issues prevent login
     */
    public function verifyLogin(array $data, Request $request): array
    {
        $identifier = $data['identifier'];
        $otp = $data['otp'];
        
        $user = $this->getUserByIdentifier($identifier);
        
        $type = $this->inputDetectionService->detectType($identifier);
        if (!$this->otpService->verifyOtp($user, $otp, $type)) {
            throw ValidationException::withMessages([
                'otp' => [self::ERROR_INVALID_OTP],
            ]);
        }
        
        $this->validateUserAccount($user);

        // Check registration payment requirements
        $paymentResponse = $this->checkRegistrationPayment($user);
        if ($paymentResponse) {
            return $paymentResponse;
        }

        // Complete OTP-based login
        $deviceName = $data['device_name'] ?? $request->ip();
        $token = $user->createToken($deviceName)->plainTextToken;
        if ($this->isStatefulSPARequest($request)) {
            $this->loginWebSession($user, $request);
        }

        $this->sessionService->createSession($user, $request);
        $this->checkUnusualLoginLocation($user);
        $this->checkNewDevice($user);

        // Handle device trust
        $deviceToken = null;
        if (!empty($data['remember_device'])) {
            $deviceToken = $this->createTrustedDeviceToken($user);
        }

        $this->userActivityService->loginSuccessful($user, [
            'login_method' => self::LOGIN_METHOD_OTP,
            'device_name' => $deviceName
        ]);

        $response = [
            'success' => true,
            'message' => 'Login successful',
        ];

        $response = array_merge($response, $this->buildLoginResponse($user, $token, $request, $deviceToken));

        return $response;
    }

    /**
     * Logout user from current session
     * 
     * @param User $user Authenticated user to logout
     * @return void
     * @throws \Exception When logout process fails
     */
    public function logout(User $user): void
    {
        try {
            $currentSession = $this->sessionService->getCurrentSession($user);
            if ($currentSession) {
                $this->sessionService->deactivateSession($currentSession);
            }

            $accessToken = $user->currentAccessToken();
            if ($accessToken instanceof PersonalAccessToken) {
                $accessToken->delete();
            }

            $web = Auth::guard('web');
            if ($web->check() && (int) $web->id() === (int) $user->id) {
                $web->logout();
                if (request()->hasSession()) {
                    request()->session()->invalidate();
                    request()->session()->regenerateToken();
                }
            }

            $this->userActivityService->logout($user);
        } catch (\Exception $e) {
            $this->handleAuthError($e, 'Logout');
            throw $e;
        }
    }

    /**
     * Logout user from all devices and sessions
     * 
     * @param User $user Authenticated user to logout from all devices
     * @return void
     * @throws \Exception When logout process fails
     */
    public function logoutFromAllDevices(User $user): void
    {
        try {
            $this->sessionService->deactivateUserSessions($user);
            $user->tokens()->delete();

            $web = Auth::guard('web');
            if ($web->check() && (int) $web->id() === (int) $user->id) {
                $web->logout();
                if (request()->hasSession()) {
                    request()->session()->invalidate();
                    request()->session()->regenerateToken();
                }
            }
        } catch (\Exception $e) {
            $this->handleAuthError($e, 'LogoutAll');
            throw $e;
        }
    }

    /**
     * Rotate bearer token or regenerate web session (SPA cookie auth).
     *
     * @return array{type: 'bearer', token: string, token_type: string}|array{type: 'session'}
     */
    public function refreshToken(User $user): array
    {
        try {
            $accessToken = $user->currentAccessToken();
            if ($accessToken instanceof PersonalAccessToken) {
                $accessToken->delete();
                $plain = $user->createToken('auth-token')->plainTextToken;

                return [
                    'type' => 'bearer',
                    'token' => $plain,
                    'token_type' => self::TOKEN_TYPE,
                ];
            }

            $web = Auth::guard('web');
            if (request()->hasSession() && $web->check() && (int) $web->id() === (int) $user->id) {
                request()->session()->regenerate();

                return ['type' => 'session'];
            }

            throw new \RuntimeException('No refreshable credential');
        } catch (\Exception $e) {
            $this->handleAuthError($e, 'RefreshToken');
            throw $e;
        }
    }

    /**
     * Initiate password reset process
     * 
     * @param string $email User email address
     * @return void
     * @throws \Exception When account is deactivated
     */
    public function forgotPassword(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return; // Return silently for security
        }

        $this->validateUserAccount($user);

        $user->sendPasswordResetNotification(
            Password::createToken($user)
        );

        Log::info('Password reset requested for user: ' . $user->id, [
            'email' => $email
        ]);
    }

    /**
     * Complete password reset with new password
     * 
     * @param array $data Password reset data (email, token, password)
     * @return string Password reset status
     * @throws \Exception When user not found or account deactivated
     */
    public function resetPassword(array $data): string
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            throw new \Exception(self::ERROR_USER_NOT_FOUND, 404);
        }

        $this->validateUserAccount($user);

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

    /**
     * Find user by identifier (email or phone)
     */
    private function findUserByIdentifier(string $identifier): ?User
    {
        $type = $this->inputDetectionService->detectType($identifier);
        
        if (!$type) {
            return null;
        }
        
        if ($type === 'email') {
            return User::where('email', $identifier)->first();
        }
        
        if ($type === 'phone') {
            if (!$this->inputDetectionService->isValidPhone($identifier)) {
                return null;
            }
            return User::where('phone', $identifier)->first();
        }
        
        return null;
    }

    /**
     * Check if user requires registration payment and return payment details if needed
     */
    private function checkRegistrationPayment(User $user): ?array
    {
        $registrationStatus = $user->registration_fee_status ?? null;
        
        if ($registrationStatus === self::FEE_STATUS_PAID || 
            $registrationStatus === self::FEE_STATUS_NOT_REQUIRED || 
            $user->role === self::ROLE_STUDENT) {
            return null;
        }
        
        $paymentResult = $this->registrationPaymentService->getOrCreateCheckoutUrl($user, 'api');
        
        if (!empty($paymentResult['checkout_url'])) {
            $orderId = $paymentResult['order_id'] ?? '';
            return [
                'requires_registration_payment' => true,
                'payment_link'                  => $this->buildProxyPaymentUrl($orderId),
                'payment_session_id'            => $paymentResult['payment_session_id'] ?? null,
                'order_id'                      => $orderId,
                'actual_price'                  => $paymentResult['actual_price'] ?? null,
                'discounted_price'              => $paymentResult['discounted_price'] ?? null,
                'description'                   => $paymentResult['description'] ?? null,
                'role'                          => $user->role,
                'message'                       => 'Registration fee payment is required to complete login.',
            ];
        }
        
        if (!($paymentResult['success'] ?? false) && !empty($paymentResult['message'])) {
            throw new \Exception($paymentResult['message'], 403);
        }
        
        return null;
    }

    /**
     * Build standard user response data
     */
    private function buildUserResponse(User $user): array
    {
        $response = $user->only(['id', 'name', 'email', 'role']);
        
        if ($user->phone) {
            $response['phone_verified_at'] = $user->phone_verified_at ?? null;
        }
        
        return $response;
    }

    /**
     * Build successful login response
     */
    private function buildLoginResponse(User $user, string $token, Request $request, ?string $deviceToken = null): array
    {
        $response = [
            'user' => $this->buildUserResponse($user),
            'email_verified_at' => $user->email_verified_at,
            'registration_fee_status' => in_array($user->registration_fee_status, [self::FEE_STATUS_PAID, self::FEE_STATUS_NOT_REQUIRED]),
            'auth_mode' => $this->isStatefulSPARequest($request) ? 'both' : 'token',
            'token' => $token,
            'token_type' => self::TOKEN_TYPE,
        ];

        if ($deviceToken) {
            $response['device_token'] = $deviceToken;
        }

        return $response;
    }

    /**
     * Build registration response
     */
    private function buildRegistrationResponse(User $user, string $token, bool $requiresPayment, ?array $registrationCharges, Request $request): array
    {
        return [
            'user' => $user->only(['id', 'name', 'email', 'role', 'email_verified_at', 'phone_verified_at', 'registration_fee_status']),
            'auth_mode' => $this->isStatefulSPARequest($request) ? 'both' : 'token',
            'token' => $token,
            'token_type' => self::TOKEN_TYPE,
            'next_step' => 'email_verification',
            'requires_registration_payment' => $requiresPayment,
            'registration_charges' => $registrationCharges,
        ];
    }

    /**
     * Build OTP required response
     */
    private function buildOtpRequiredResponse(string $identifier, string $type, string $message): array
    {
        return [
            'requires_otp' => true,
            'identifier' => $identifier,
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * Handle and log authentication errors consistently
     */
    private function handleAuthError(\Exception $e, string $operation, array $context = []): void
    {
        Log::error("AuthService {$operation} failed: " . $e->getMessage(), array_merge([
            'trace' => $e->getTraceAsString()
        ], $context));
    }

    /**
     * Validate user account status and throw appropriate exceptions
     */
    private function validateUserAccount(User $user): void
    {
        if (!$user->is_active) {
            throw new \Exception(self::ERROR_ACCOUNT_DEACTIVATED, 403);
        }
    }

    /**
     * Validate user email verification for login
     */
    private function validateEmailVerification(User $user): void
    {
        return; // Email verification is optional for now, so we skip this check
    }

    /**
     * Execute operation with graceful failure handling
     */
    private function executeGracefully(callable $operation, string $errorMessage): void
    {
        try {
            $operation();
        } catch (\Exception $e) {
            Log::error($errorMessage . ': ' . $e->getMessage());
        }
    }

    /**
     * Check if the current device is trusted for the user
     */
    private function isTrustedDevice(User $user, Request $request): bool
    {
        $deviceToken = $request->header('X-Device-Token');
        
        if (!$deviceToken) {
            return false;
        }

        $cacheKey = 'trusted_device:' . $user->id . ':' . $deviceToken;
        return \Illuminate\Support\Facades\Cache::has($cacheKey);
    }

    /**
     * Handle OTP requirement for untrusted devices
     */
    private function handleOtpRequirement(User $user, string $identifier, string $type): array
    {
        $otpType = $type;
        if ($type === 'phone' && empty($user->phone)) {
            $otpType = 'email';
        }
        
        $this->otpService->sendOtp($user, $otpType);

        return $this->buildOtpRequiredResponse(
            $identifier, 
            $otpType, 
            "OTP sent to your {$otpType}. Please verify to complete login."
        );
    }

    /**
     * Complete login process for trusted devices
     */
    private function completeTrustedLogin(User $user, array $credentials, Request $request): array
    {
        $deviceName = $credentials['device_name'] ?? $request->ip();
        $token = $user->createToken($deviceName)->plainTextToken;
        if ($this->isStatefulSPARequest($request)) {
            $this->loginWebSession($user, $request);
        }

        $this->sessionService->createSession($user, $request);
        $this->checkUnusualLoginLocation($user);
        $this->checkNewDevice($user);

        $this->userActivityService->loginSuccessful($user, [
            'login_method' => self::LOGIN_METHOD_TRUSTED,
            'device_name' => $deviceName
        ]);

        return $this->buildLoginResponse($user, $token, $request);
    }

    /**
     * First-party browser SPA (Sanctum): Origin/Referer must match config('sanctum.stateful').
     * Mobile/native and tools without Origin continue to use personal access tokens.
     */
    private function isStatefulSPARequest(Request $request): bool
    {
        return EnsureFrontendRequestsAreStateful::fromFrontend($request);
    }

    /**
     * Cookie session for SPA; works with auth:sanctum via config('sanctum.guard') => web.
     */
    private function loginWebSession(User $user, Request $request): void
    {
        Auth::guard('web')->login($user, false);
        $request->session()->regenerate();
    }

    /**
     * Create trusted device token and cache it
     */
    private function createTrustedDeviceToken(User $user): string
    {
        $deviceToken = \Illuminate\Support\Str::random(self::TRUSTED_DEVICE_TOKEN_LENGTH);
        $cacheKey = 'trusted_device:' . $user->id . ':' . $deviceToken;
        \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addDays(self::TRUSTED_DEVICE_CACHE_DAYS));
        
        return $deviceToken;
    }

    /**
     * Validate identifier and get user with proper error handling
     */
    private function getUserByIdentifier(string $identifier): User
    {
        $type = $this->inputDetectionService->detectType($identifier);
        
        if (!$type) {
            throw ValidationException::withMessages([
                'identifier' => [self::ERROR_INVALID_IDENTIFIER_FORMAT],
            ]);
        }
        
        $user = null;
        if ($type === 'email') {
            $user = User::where('email', $identifier)->first();
        } elseif ($type === 'phone') {
            if (!$this->inputDetectionService->isValidPhone($identifier)) {
                throw ValidationException::withMessages([
                    'identifier' => [self::ERROR_INVALID_PHONE_FORMAT],
                ]);
            }
            $user = User::where('phone', $identifier)->first();
        }
        
        if (!$user) {
            throw ValidationException::withMessages([
                'identifier' => [self::ERROR_USER_NOT_FOUND],
            ]);
        }
        
        return $user;
    }

    /**
     * Build the proxy checkout URL for a given order.
     *
     * Points to OUR endpoint (not directly to Cashfree) so that the link
     * never expires — every click triggers a fresh Cashfree session lookup.
     *
     * Strips any path component from APP_URL so doubled segments like
     * "/api/v1/api/v1" cannot occur if APP_URL is misconfigured.
     */
    private function buildProxyPaymentUrl(string $orderId): string
    {
        $parsed  = parse_url(rtrim(config('app.url', 'http://localhost'), '/'));
        $baseUrl = ($parsed['scheme'] ?? 'https') . '://' . ($parsed['host'] ?? 'localhost');

        if (!empty($parsed['port'])) {
            $baseUrl .= ':' . $parsed['port'];
        }

        return $baseUrl . '/api/v1/payment/checkout?order_id=' . $orderId;
    }
}
