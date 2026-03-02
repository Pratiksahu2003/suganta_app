<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Otp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure necessary tables exist (copied from TwoFactorLoginTest/ApiResponseTest setup)
        if (\Schema::hasTable('users')) {
            \Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                if (!\Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (!\Schema::hasColumn('users', 'role')) {
                    $table->string('role')->nullable();
                }
                if (!\Schema::hasColumn('users', 'phone')) {
                    $table->string('phone')->nullable();
                }
                if (!\Schema::hasColumn('users', 'verification_status')) {
                    $table->string('verification_status')->default('pending');
                }
                if (!\Schema::hasColumn('users', 'registration_fee_status')) {
                    $table->string('registration_fee_status')->nullable();
                }
                if (!\Schema::hasColumn('users', 'phone_verified_at')) {
                    $table->timestamp('phone_verified_at')->nullable();
                }
                if (!\Schema::hasColumn('users', 'preferences')) {
                    $table->json('preferences')->nullable();
                }
            });
        }
        
        if (!\Schema::hasTable('otps')) {
            \Schema::create('otps', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->string('identifier');
                $table->string('type');
                $table->string('otp');
                $table->timestamp('expires_at');
                $table->boolean('verified')->default(false);
                $table->boolean('is_used')->default(false);
                $table->integer('attempt_count')->default(0);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_send_otp_via_email_for_valid_email_user()
    {
        $user = User::factory()->create([
            'email' => 'otp_user@example.com',
            'phone' => '+1234567890',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login/send-otp', [
            'identifier' => 'otp_user@example.com'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent to your email.'
            ]);

        $this->assertDatabaseHas('otps', [
            'identifier' => 'otp_user@example.com',
            'type' => 'email',
            'is_used' => false,
            'user_id' => $user->id
        ]);
    }

    public function test_send_otp_via_sms_for_valid_phone_user()
    {
        $user = User::factory()->create([
            'email' => 'phone_user@example.com',
            'phone' => '+9876543210',
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/v1/auth/login/send-otp', [
            'identifier' => '+9876543210'
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'OTP sent to your phone.'
            ]);

        $this->assertDatabaseHas('otps', [
            'identifier' => '+9876543210',
            'type' => 'phone',
            'is_used' => false,
            'user_id' => $user->id
        ]);
    }

    public function test_send_otp_fails_for_invalid_identifier_format()
    {
        $response = $this->postJson('/api/v1/auth/login/send-otp', [
            'identifier' => 'invalid-format'
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid identifier format'
            ]);
    }

    public function test_send_otp_fails_if_user_not_found()
    {
        $response = $this->postJson('/api/v1/auth/login/send-otp', [
            'identifier' => 'nonexistent@example.com'
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'success' => false,
                'message' => 'User not found'
            ]);
    }

    public function test_send_otp_fails_if_user_inactive()
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login/send-otp', [
            'identifier' => 'inactive@example.com'
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Account is deactivated'
            ]);
    }

    public function test_rate_limiting_on_send_otp()
    {
        $user = User::factory()->create([
            'email' => 'rate_limit@example.com',
            'is_active' => true,
        ]);

        // 1st request
        $this->postJson('/api/v1/auth/login/send-otp', ['identifier' => 'rate_limit@example.com']);
        
        // 2nd request (should be blocked by 30s cooldown from OtpService logic)
        // Recall logic: 1st attempt (0 previous) -> allow, next cooldown 30s.
        // So immediate 2nd request should fail.
        $response = $this->postJson('/api/v1/auth/login/send-otp', ['identifier' => 'rate_limit@example.com']);

        $response->assertStatus(429);
    }
}
