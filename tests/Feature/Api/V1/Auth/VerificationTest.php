<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Models\User;
use App\Models\Role;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;

class VerificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();
        // Create roles
        Role::create(['name' => 'student', 'label' => 'Student']);
        
        // Create user
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'is_active' => true,
        ]);
        
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_resend_otp()
    {
        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->postJson('/api/v1/auth/verification/resend', [
                'type' => 'email'
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Verification code sent.']);

        $this->assertDatabaseHas('otps', [
            'user_id' => $this->user->id,
            'type' => 'email',
            'verified' => false
        ]);
    }

    public function test_can_verify_otp()
    {
        // Create OTP
        $otpCode = '123456';
        Otp::create([
            'user_id' => $this->user->id,
            'identifier' => $this->user->email,
            'type' => 'email',
            'otp' => Hash::make($otpCode),
            'expires_at' => now()->addMinutes(10),
            'verified' => false,
        ]);

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $this->token])
            ->postJson('/api/v1/auth/verification/verify', [
                'type' => 'email',
                'otp' => $otpCode
            ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Email verified successfully.']);

        $this->user->refresh();
        $this->assertNotNull($this->user->email_verified_at);
        
        $this->assertDatabaseHas('otps', [
            'user_id' => $this->user->id,
            'verified' => true
        ]);
    }
}
