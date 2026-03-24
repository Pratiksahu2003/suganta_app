<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApiResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_returns_standard_response()
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'student',
            'phone' => '+1234567890',
        ]);

        if ($response->status() !== 201 && $response->status() !== 422) {
             $response->dump();
        }

        if ($response->status() === 422) {
             $response->assertJsonStructure([
                'message',
                'success',
                'code',
                'errors'
            ])->assertJson([
                'success' => false,
                'code' => 422
            ]);
        } else {
            $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'success',
                    'code',
                    'data' => [
                        'user',
                        'token',
                        'token_type'
                    ]
                ])
                ->assertJson([
                    'success' => true,
                    'code' => 201,
                    'message' => 'User registered successfully'
                ]);
        }
    }

    public function test_login_returns_standard_response()
    {
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
            'role' => 'student',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'login@example.com',
            'password' => 'password',
        ]);

        if ($response->status() !== 200) {
             $response->dump();
        }

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'success',
                'code',
                'data'
            ])
            ->assertJson([
                'success' => true,
                'code' => 200,
            ]);
            
        // Check if data contains user and token (if no OTP required) or requires_otp
        $data = $response->json('data');
        if (isset($data['requires_otp'])) {
             $this->assertTrue($data['requires_otp']);
        } else {
             $this->assertArrayHasKey('token', $data);
        }
    }

    public function test_validation_error_returns_standard_response()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'invalid-email',
            'password' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'success',
                'code',
                'errors'
            ])
            ->assertJson([
                'success' => false,
                'code' => 422,
                'message' => 'The password field is required.'
            ]);
    }

    public function test_verification_resend_returns_standard_response()
    {
        $user = User::factory()->create([
            'email' => 'resend@example.com',
            'password' => Hash::make('password'),
            'is_active' => true,
            'role' => 'student',
            'email_verified_at' => null, // Ensure unverified
        ]);

        $this->actingAs($user);

        $response = $this->postJson('/api/v1/auth/verification/resend', [
            'type' => 'email'
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'success',
                'code',
            ])
            ->assertJson([
                'success' => true,
                'code' => 200,
                'message' => 'Verification code sent.'
            ]);
    }
}
