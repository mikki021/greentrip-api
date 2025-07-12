<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailVerificationControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_verify_email_with_valid_token()
    {
        $user = User::factory()->unverified()->create([
            'email_verification_token' => 'valid-token-123',
            'email_verification_expires_at' => Carbon::now()->addHour(),
        ]);

        $response = $this->getJson('/api/verify-email/valid-token-123');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Email verified successfully. You can now log in.',
            ])
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email_verification_token' => null,
            'email_verification_expires_at' => null,
        ]);

        $verifiedUser = \App\Models\User::find($user->id);
        $this->assertNotNull($verifiedUser->email_verified_at);
    }

    /** @test */
    public function it_returns_error_for_invalid_token()
    {
        $response = $this->getJson('/api/verify-email/invalid-token');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid or expired verification token.',
            ]);
    }

    /** @test */
    public function it_returns_error_for_expired_token()
    {
        $user = User::factory()->unverified()->create([
            'email_verification_token' => 'expired-token-123',
            'email_verification_expires_at' => Carbon::now()->subHour(),
        ]);

        $response = $this->getJson('/api/verify-email/expired-token-123');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid or expired verification token.',
            ]);
    }

    /** @test */
    public function it_can_resend_verification_email()
    {
        $user = User::factory()->unverified()->create([
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson('/api/resend-verification', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Verification email sent successfully.',
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verification_token);
        $this->assertNotNull($user->email_verification_expires_at);
    }

    /** @test */
    public function it_returns_error_for_resend_with_nonexistent_email()
    {
        $response = $this->postJson('/api/resend-verification', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    /** @test */
    public function it_returns_error_for_resend_to_already_verified_email()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'email_verified_at' => now(),
        ]);

        $response = $this->postJson('/api/resend-verification', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    /** @test */
    public function it_returns_validation_error_for_invalid_email_format()
    {
        $response = $this->postJson('/api/resend-verification', [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }

    /** @test */
    public function it_returns_validation_error_for_missing_email()
    {
        $response = $this->postJson('/api/resend-verification', []);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors' => [
                    'email',
                ],
            ]);
    }
}