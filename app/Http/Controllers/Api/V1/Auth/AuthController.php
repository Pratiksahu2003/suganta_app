<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use ApiResponse;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * SPA / client: check if the current request is authenticated (session cookie or Bearer token).
     * Does not return 401 when logged out — use `authenticated` in the payload.
     */
    public function me(Request $request): JsonResponse
    {
        $user = Auth::guard('sanctum')->user();

        if (! $user) {
            return $this->success('Not authenticated', [
                'authenticated' => false,
                'user' => null,
            ]);
        }

        $authMode = $request->bearerToken() ? 'token' : 'session';

        return $this->success('Authenticated', [
            'authenticated' => true,
            'auth_mode' => $authMode,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'phone' => $user->phone,
                'email_verified_at' => $user->email_verified_at,
                'phone_verified_at' => $user->phone_verified_at,
                'registration_fee_status' => $user->registration_fee_status,
                'verification_status' => $user->verification_status,
            ],
        ]);
    }

    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated(), $request);

            return $this->created($result, 'User registered successfully');
        } catch (\Exception $e) {
            Log::error('API Registration failed: ' . $e->getMessage());
            return $this->serverError('Registration failed. Please try again.', $e);
        }
    }

    /**
     * Login user and return token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated(), $request);

            if (isset($result['requires_registration_payment']) && $result['requires_registration_payment']) {
                return $this->coreResponse(
                    $result['message'] ?? 'Registration fee payment is required to complete login.',
                    $result,
                    200,
                    false
                );
            }

            if (isset($result['requires_otp']) && $result['requires_otp']) {
                return $this->success($result['message'], $result);
            }

            return $this->success('Login successful', $result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Invalid credentials');
        } catch (\Exception $e) {
            Log::error('API Login failed: ' . $e->getMessage());
            
            $statusCode = $e->getCode();
            // Ensure status code is valid HTTP status code
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }
            
            if ($statusCode === 403) {
                return $this->forbidden($e->getMessage());
            }
            
            return $this->serverError($e->getMessage() ?: 'Login failed. Please try again.', $e);
        }
    }

    /**
     * Send OTP for login
     */
    public function sendLoginOtp(Request $request): JsonResponse
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        try {
            $result = $this->authService->sendLoginOtp($request->identifier);

            return $this->success($result['message'], $result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Invalid identifier');
        } catch (\Exception $e) {
            $statusCode = $e->getCode();
            // Ensure status code is valid HTTP status code
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }

            if ($statusCode === 404) {
                 return $this->notFound('User not found');
            }
            
            if ($statusCode === 403) {
                return $this->forbidden($e->getMessage());
            }

            // For rate limiting (abort(429) throws HttpException which isn't caught by catch(\Exception) in older Laravel versions, 
            // but in newer it is. Wait, abort() throws HttpException which extends Exception.
            // But let's check if we need special handling. 
            // abort(429) usually renders a response automatically.
            // But if we catch \Exception, we catch it.
            // So we need to rethrow if it's HttpException or handle it.
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpException) {
                 // return $this->error($e->getMessage(), $e->getStatusCode());
                 // Or just let Laravel handle it?
                 // If I catch it, I must return response.
                 return $this->error($e->getMessage(), $e->getStatusCode());
            }
            
            Log::error('API Send Login OTP failed: ' . $e->getMessage());
            return $this->serverError('Failed to send OTP. Please try again.', $e);
        }
    }

    /**
     * Verify Login OTP
     */
    public function verifyLogin(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'identifier' => 'required|string',
            'otp' => 'required|string',
            'remember_device' => 'nullable|boolean',
            'device_name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors(), 'Verification failed due to invalid input');
        }

        try {
            $result = $this->authService->verifyLogin($request->all(), $request);

            if (isset($result['requires_registration_payment']) && $result['requires_registration_payment']) {
                return $this->coreResponse(
                    $result['message'] ?? 'Registration fee payment is required to complete login.',
                    $result,
                    200,
                    false
                );
            }

            return $this->success('Login successful', $result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationError($e->errors(), 'Verification failed due to invalid input');
        } catch (\Exception $e) {
            Log::error('API Login Verification failed due to invalid input: ' . $e->getMessage());

            $statusCode = $e->getCode();
            if ($statusCode < 100 || $statusCode > 599) {
                $statusCode = 500;
            }

            if ($statusCode === 403) {
                return $this->forbidden($e->getMessage());
            }

            return $this->serverError('Verification failed. Please try again.', $e);
        }
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());

            return $this->success('Logged out successfully');
        } catch (\Exception $e) {
            return $this->serverError('Logout failed', $e);
        }
    }

    /**
     * Logout from all devices
     */
    public function logoutFromAllDevices(Request $request): JsonResponse
    {
        try {
            $this->authService->logoutFromAllDevices($request->user());

            return $this->success('Logged out from all devices successfully');
        } catch (\Exception $e) {
            return $this->serverError('Logout failed', $e);
        }
    }

    /**
     * Refresh user token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        try {
            $result = $this->authService->refreshToken($request->user());

            if (($result['type'] ?? '') === 'bearer') {
                return $this->success('Token refreshed successfully', [
                    'auth_mode' => 'token',
                    'token' => $result['token'],
                    'token_type' => $result['token_type'],
                ]);
            }

            return $this->success('Session refreshed successfully', [
                'auth_mode' => 'session',
            ]);
        } catch (\Exception $e) {
            return $this->serverError('Token refresh failed', $e);
        }
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->forgotPassword($request->validated()['email']);

            return $this->success('If an account with that email exists, a password reset link has been sent.');
        } catch (\Exception $e) {
            $statusCode = $e->getCode();
            if ($statusCode >= 100 && $statusCode < 600) {
                if ($statusCode === 403) {
                    return $this->forbidden($e->getMessage());
                }
            }
            Log::error('API Forgot Password failed: ' . $e->getMessage());
            return $this->serverError('Password reset request failed. Please try again.', $e);
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = $this->authService->resetPassword($request->validated());

            if ($status === \Illuminate\Support\Facades\Password::PASSWORD_RESET) {
                return $this->success('Password has been reset successfully. Please login with your new password.');
            } else {
                return $this->error('Invalid or expired reset token', 400);
            }
        } catch (\Exception $e) {
            $statusCode = $e->getCode();
            if ($statusCode >= 100 && $statusCode < 600) {
                if ($statusCode === 403) {
                    return $this->forbidden($e->getMessage());
                }
                if ($statusCode === 404) {
                    return $this->error('Invalid or expired reset token', 400);
                }
            }
            Log::error('API Reset Password failed: ' . $e->getMessage());
            return $this->serverError('Password reset failed. Please try again.', $e);
        }
    }
}
