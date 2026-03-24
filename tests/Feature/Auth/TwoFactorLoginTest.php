<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_triggers_otp_for_untrusted_device()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
            'role' => 'student',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'requires_otp' => true,
                    'identifier' => 'test@example.com',
                    'type' => 'email',
                ]
            ]);

        $this->assertDatabaseHas('otps', [
            'identifier' => 'test@example.com',
            'is_used' => false,
        ]);
    }

    public function test_verify_login_otp_issues_token()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
            'role' => 'student',
        ]);

        // Create OTP manually
        $otpCode = '123456';
        Otp::create([
            'user_id' => $user->id,
            'identifier' => 'test@example.com',
            'type' => 'email',
            'otp' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(5),
            'is_used' => false,
            'attempt_count' => 0,
        ]);

        $response = $this->postJson('/api/v1/auth/login/verify', [
            'identifier' => 'test@example.com',
            'otp' => $otpCode,
            'device_name' => 'Test Device',
        ]);

        if ($response->status() !== 200) {
             $response->dump();
        }

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ])
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user',
                ]
            ]);
    }

    public function test_rate_limiting_otp_requests()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
            'role' => 'student',
        ]);

        // 1st request
        $this->postJson('/api/v1/auth/login', ['email' => 'test@example.com', 'password' => 'password']);
        
        // 2nd request (should be allowed after cooldown? No, first 3 are allowed immediately in my implementation logic? 
        // Wait, my implementation logic: 
        // if attempts=0 -> allow, next wait 30s.
        // So 2nd request immediately after 1st should be blocked by cooldown key.
        
        $response = $this->postJson('/api/v1/auth/login', ['email' => 'test@example.com', 'password' => 'password']);
        
        if ($response->status() !== 429) {
             $response->dump();
        }
        
        $response->assertStatus(429); // Too Many Requests (Cooldown)
    }
}
