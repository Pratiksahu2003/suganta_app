<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Services\SessionService;
use App\Services\UserActivityNotificationService;
use App\Services\PasswordNotificationService;
use App\Services\RegistrationPaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Models\Profile;
class AuthController extends Controller
{
    protected SessionService $sessionService;
    protected UserActivityNotificationService $userActivityService;
    protected PasswordNotificationService $passwordNotificationService;
    protected RegistrationPaymentService $registrationPaymentService;

    public function __construct(
        SessionService $sessionService,
        UserActivityNotificationService $userActivityService,
        PasswordNotificationService $passwordNotificationService,
        RegistrationPaymentService $registrationPaymentService
    ) {
        $this->sessionService = $sessionService;
        $this->userActivityService = $userActivityService;
        $this->passwordNotificationService = $passwordNotificationService;
        $this->registrationPaymentService = $registrationPaymentService;
    }

    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
                'role' => ['required', 'string', 'in:student,teacher,institute,ngo'],
                'phone' => ['nullable', 'string', 'max:20'],
                'referral_code' => ['nullable', 'string', 'max:20'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Create the user
            $user = User::create([
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
                'phone' => $validated['phone'] ?? null,
                'is_active' => true,
                'verification_status' => 'pending',
                'referred_by' => $validated['referral_code'] ?? null,
                'preferences' => [
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                ],
            ]);

            // Assign role
            $this->assignUserRole($user, $validated['role']);

            // Create token
            $token = $user->createToken('auth-token')->plainTextToken;
            Profile::create([
                'user_id' => $user->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'display_name' => $validated['first_name'] . ' ' . $validated['last_name'],
            ]);
            // Create session record
            $this->sessionService->createSession($user, $request);

            // Send registration notification
            $this->userActivityService->loginSuccessful($user, [
                'login_method' => 'api_registration'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email', 'role']),
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('API Registration failed: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Login user and return token
     */
    public function login(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string'],
                'device_name' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Attempt authentication
            if (!Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
                // Log failed attempt
                $user = User::where('email', $validated['email'])->first();

                if ($user) {
                    $this->userActivityService->loginFailed($user, 'Invalid password via API');
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ], 422);
            }

            $user = Auth::user();

            // Check if user is active
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is deactivated'
                ], 403);
            }

            if ($user->email_verified_at === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not verified. Please verify your email before logging in.'
                ], 403);
            }

            // If registration fee is not paid, return payment link (same flow as POST /registration/payment)
            $registrationStatus = $user->registration_fee_status ?? null;
            if ($registrationStatus !== 'paid' && $registrationStatus !== 'not_required' && $user->role != 'student') {
                $paymentResult = $this->registrationPaymentService->getOrCreateCheckoutUrl($user, 'api');

                if (!empty($paymentResult['checkout_url'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Registration fee payment is required to complete login.',
                        'requires_registration_payment' => true,
                        'payment_link' => $paymentResult['checkout_url'],
                        'order_id' => $paymentResult['order_id'] ?? null,
                        'actual_price' => $paymentResult['actual_price'] ?? null,
                        'discounted_price' => $paymentResult['discounted_price'] ?? null,
                        'description' => $paymentResult['description'] ?? null,
                        'role' => $user->role
                    ], 200);
                }
                if (!($paymentResult['success'] ?? false) && !empty($paymentResult['message'])) {
                    return response()->json([
                        'success' => false,
                        'message' => $paymentResult['message'],
                    ], 403);
                }
                // already_paid after service (e.g. role not required) — fall through to success
            }

            // Create token
            $deviceName = $validated['device_name'] ?? $request->ip();
            $token = $user->createToken($deviceName)->plainTextToken;

            // Create session record
            $this->sessionService->createSession($user, $request);

            // Check for unusual login location
            $this->checkUnusualLoginLocation($user);

            // Check for new device
            $this->checkNewDevice($user);

            // Send login notification
            $this->userActivityService->loginSuccessful($user, [
                'login_method' => 'api_email_password',
                'device_name' => $deviceName
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => $user->only(['id', 'name', 'email', 'role']),
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Login failed: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user) {
                // Deactivate current session
                $currentSession = $this->sessionService->getCurrentSession($user);
                if ($currentSession) {
                    $this->sessionService->deactivateSession($currentSession);
                }

                // Revoke current token
                $request->user()->currentAccessToken()->delete();

                // Send logout notification
                $this->userActivityService->logout($user);
            }

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('API Logout failed: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Logout from all devices
     */
    public function logoutFromAllDevices(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user) {
                // Deactivate all sessions
                $this->sessionService->deactivateUserSessions($user);

                // Revoke all tokens
                $user->tokens()->delete();
            }

            return response()->json([
                'success' => true,
                'message' => 'Logged out from all devices successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('API Logout all devices failed: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Logout failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Refresh user token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            // Create new token
            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Token refreshed successfully',
                'data' => [
                    'token' => $token,
                    'token_type' => 'Bearer'
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('API Token refresh failed: ' . $e->getMessage(), [
                'user_id' => $request->user()?->id ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token refresh failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => ['required', 'string', 'email'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $email = $validated['email'];

            // Check if user exists
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Return success even if user doesn't exist for security
                return response()->json([
                    'success' => true,
                    'message' => 'If an account with that email exists, a password reset link has been sent.'
                ]);
            }

            // Check if user is active
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is deactivated'
                ], 403);
            }

            // Send password reset notification
            $user->sendPasswordResetNotification(
                Password::createToken($user)
            );

            // Log the password reset request
            Log::info('Password reset requested for user: ' . $user->id, [
                'email' => $email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password reset link has been sent to your email address.'
            ]);
        } catch (\Exception $e) {
            Log::error('API Forgot password failed: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Password reset request failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'token' => ['required', 'string'],
                'email' => ['required', 'string', 'email'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();
            $email = $validated['email'];
            $token = $validated['token'];
            $password = $validated['password'];

            // Check if user exists
            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Check if user is active
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is deactivated'
                ], 403);
            }

            // Reset the password using Laravel's built-in functionality
            $status = Password::reset([
                'email' => $email,
                'password' => $password,
                'password_confirmation' => $request->password_confirmation,
                'token' => $token
            ], function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->save();
            });

            if ($status === Password::PASSWORD_RESET) {
                // Log the successful password reset
                Log::info('Password reset successful for user: ' . $user->id, [
                    'email' => $email,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                // Send password reset notification
                $this->passwordNotificationService->passwordReset($user, [
                    'reset_method' => 'api_reset',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                // Revoke all existing tokens for security
                $user->tokens()->delete();

                return response()->json([
                    'success' => true,
                    'message' => 'Password has been reset successfully. Please login with your new password.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or expired reset token'
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('API Reset password failed: ' . $e->getMessage(), [
                'email' => $request->email ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Password reset failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Assign role to user
     */
    private function assignUserRole(User $user, string $roleName): void
    {
        try {
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
        } catch (\Exception $e) {
            Log::error("Error assigning role '{$roleName}' to user {$user->id}: " . $e->getMessage());
        }
    }

    /**
     * Check for unusual login location
     */
    private function checkUnusualLoginLocation(User $user): void
    {
        // Implementation for location checking
        // This would typically compare current IP with previous login locations
    }

    /**
     * Check for new device
     */
    private function checkNewDevice(User $user): void
    {
        // Implementation for device checking
        // This would typically check if the current device is new
    }
}
