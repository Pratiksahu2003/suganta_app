<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsCountryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;
use Carbon\Carbon;

class OtpServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $otpService;
    protected $smsService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->smsService = $this->createMock(SmsCountryService::class);
        $this->otpService = new OtpService($this->smsService);
        
        // Ensure RateLimiter is clean
        RateLimiter::clear('otp_request:test@example.com');
        RateLimiter::clear('otp_cooldown:test@example.com');
        RateLimiter::clear('otp_request:limit@example.com');
        RateLimiter::clear('otp_cooldown:limit@example.com');
    }

    public function test_otp_cooldown_enforced()
    {
        $user = User::factory()->create(['email' => 'test@example.com']);

        // First attempt should succeed
        $this->otpService->sendOtp($user, 'email');
        
        // Second attempt within 120s should fail with 429
        try {
            $this->otpService->sendOtp($user, 'email');
            $this->fail('Expected rate limit exception was not thrown.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertStringContainsString('Please wait', $e->getMessage());
        }
    }

    public function test_otp_rate_limit_enforced()
    {
        $user = User::factory()->create(['email' => 'limit@example.com']);
        $identifier = 'limit@example.com';
        
        // Clear limits
        RateLimiter::clear('otp_request:' . $identifier);
        RateLimiter::clear('otp_cooldown:' . $identifier);

        // Send 3 requests (simulating passing cooldown by clearing it)
        for ($i = 0; $i < 3; $i++) {
            $this->otpService->sendOtp($user, 'email');
            RateLimiter::clear('otp_cooldown:' . $identifier); // Clear cooldown to allow next request
        }

        // 4th request should fail with "Too many OTP requests"
        try {
            $this->otpService->sendOtp($user, 'email');
            $this->fail('Expected rate limit exception was not thrown.');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertEquals(429, $e->getStatusCode());
            $this->assertStringContainsString('Too many OTP requests', $e->getMessage());
        }
    }
}
