<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = app(AuthService::class);
    }

    /** @test */
    public function it_can_register_a_new_user()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'authservice.1@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $result = $this->authService->register($userData);

        $this->assertArrayHasKey('user', $result);
        $this->assertInstanceOf(User::class, $result['user']);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'authservice.1@example.com',
        ]);

        $this->assertTrue(Hash::check('password123', $result['user']->password));
    }

    /** @test */
    public function it_throws_validation_exception_for_invalid_registration_data()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ];

        $this->authService->register($invalidData);
    }

    /** @test */
    public function it_throws_validation_exception_for_duplicate_email()
    {
        User::factory()->create(['email' => 'authservice.2@example.com']);

        $this->expectException(ValidationException::class);

        $duplicateData = [
            'name' => 'John Doe',
            'email' => 'authservice.2@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->authService->register($duplicateData);
    }

    /** @test */
    public function it_throws_validation_exception_for_missing_password_confirmation()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'name' => 'John Doe',
            'email' => 'authservice.3@example.com',
            'password' => 'password123',
        ];

        $this->authService->register($invalidData);
    }

    /** @test */
    public function it_can_login_with_valid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'authservice.4@example.com',
            'password' => Hash::make('password123'),
        ]);

        $loginData = [
            'email' => 'authservice.4@example.com',
            'password' => 'password123',
        ];

        $result = $this->authService->login($loginData);

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertIsString($result['token']);
        $this->assertEquals($user->id, $result['user']->id);
    }

    /** @test */
    public function it_throws_validation_exception_for_invalid_login_data()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'email' => 'invalid-email',
            'password' => '',
        ];

        $this->authService->login($invalidData);
    }

    /** @test */
    public function it_throws_validation_exception_for_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'authservice.5@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->expectException(ValidationException::class);

        $invalidCredentials = [
            'email' => 'authservice.5@example.com',
            'password' => 'wrongpassword',
        ];

        $this->authService->login($invalidCredentials);
    }

    /** @test */
    public function it_throws_validation_exception_for_nonexistent_user()
    {
        $this->expectException(ValidationException::class);

        $nonexistentUser = [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ];

        $this->authService->login($nonexistentUser);
    }

    /** @test */
    public function it_can_logout_user()
    {
        $user = User::factory()->create();
        auth()->login($user);

        $this->assertTrue(auth()->check());

        $result = $this->authService->logout();

        $this->assertTrue($result);
        $this->assertFalse(auth()->check());
    }

    /** @test */
    public function it_can_get_current_user()
    {
        $user = User::factory()->create();
        auth()->login($user);

        $currentUser = $this->authService->getCurrentUser();

        $this->assertInstanceOf(User::class, $currentUser);
        $this->assertEquals($user->id, $currentUser->id);
    }

    /** @test */
    public function it_returns_null_for_current_user_when_not_authenticated()
    {
        $currentUser = $this->authService->getCurrentUser();

        $this->assertNull($currentUser);
    }

    /** @test */
    public function it_can_refresh_token()
    {
        $user = User::factory()->create();
        auth()->login($user);

        $originalToken = auth()->getToken();

        $result = $this->authService->refreshToken();

        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('token', $result);
        $this->assertInstanceOf(User::class, $result['user']);
        $this->assertIsString($result['token']);
        $this->assertNotEquals($originalToken, $result['token']);
    }

    /** @test */
    public function it_validates_required_fields_for_registration()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $this->authService->register($invalidData);
    }

    /** @test */
    public function it_validates_email_format_for_registration()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'name' => 'John Doe',
            'email' => 'invalid-email-format',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $this->authService->register($invalidData);
    }

    /** @test */
    public function it_validates_password_length_for_registration()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'name' => 'John Doe',
            'email' => 'test@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ];

        $this->authService->register($invalidData);
    }

    /** @test */
    public function it_validates_required_fields_for_login()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'email' => 'test@example.com',
        ];

        $this->authService->login($invalidData);
    }

    /** @test */
    public function it_validates_email_format_for_login()
    {
        $this->expectException(ValidationException::class);

        $invalidData = [
            'email' => 'invalid-email-format',
            'password' => 'password123',
        ];

        $this->authService->login($invalidData);
    }
}
