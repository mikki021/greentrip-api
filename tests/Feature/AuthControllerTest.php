<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_register_user_via_api()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'status',
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
                'authorization' => [
                    'token',
                    'type',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'User created successfully',
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
                'authorization' => [
                    'type' => 'bearer',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function it_returns_validation_errors_for_invalid_registration()
    {
        $invalidData = [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ];

        $response = $this->postJson('/api/auth/register', $invalidData);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'message',
                'errors',
            ])
            ->assertJson([
                'status' => 'error',
                'message' => 'Validation failed',
            ]);

        $this->assertArrayHasKey('name', $response->json('errors'));
        $this->assertArrayHasKey('email', $response->json('errors'));
        $this->assertArrayHasKey('password', $response->json('errors'));
    }

    /** @test */
    public function it_returns_error_for_duplicate_email_registration()
    {
        User::factory()->create(['email' => 'john@example.com']);

        $duplicateData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/auth/register', $duplicateData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validation failed',
            ]);

        $this->assertArrayHasKey('email', $response->json('errors'));
    }

    /** @test */
    public function it_can_login_user_via_api()
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $loginData = [
            'email' => 'john@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/auth/login', $loginData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'authorization' => [
                    'token',
                    'type',
                    'expires_in',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'authorization' => [
                    'type' => 'bearer',
                ],
            ]);

        $this->assertIsString($response->json('authorization.token'));
    }

    /** @test */
    public function it_returns_validation_errors_for_invalid_login()
    {
        $invalidData = [
            'email' => 'invalid-email',
            'password' => '',
        ];

        $response = $this->postJson('/api/auth/login', $invalidData);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validation failed',
            ]);

        $this->assertArrayHasKey('email', $response->json('errors'));
        $this->assertArrayHasKey('password', $response->json('errors'));
    }

    /** @test */
    public function it_returns_unauthorized_for_invalid_credentials()
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
        ]);

        $invalidCredentials = [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ];

        $response = $this->postJson('/api/auth/login', $invalidCredentials);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Validation failed',
            ]);
    }

    /** @test */
    public function it_can_get_current_user_via_api()
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
            ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_me_endpoint_without_token()
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_logout_user_via_api()
    {
        $user = User::factory()->create();
        $token = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Successfully logged out',
            ]);
    }

    /** @test */
    public function it_returns_unauthorized_for_logout_without_token()
    {
        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_refresh_token_via_api()
    {
        $user = User::factory()->create();
        $originalToken = auth()->login($user);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $originalToken,
        ])->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'authorization' => [
                    'token',
                    'type',
                    'expires_in',
                ],
            ])
            ->assertJson([
                'status' => 'success',
                'authorization' => [
                    'type' => 'bearer',
                ],
            ]);

        $newToken = $response->json('authorization.token');
        $this->assertNotEquals($originalToken, $newToken);
        $this->assertIsString($newToken);
    }

    /** @test */
    public function it_returns_unauthorized_for_refresh_without_token()
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_returns_api_info()
    {
        $response = $this->getJson('/api');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'GreenTrip API',
                'version' => '1.0.0',
            ]);
    }
}
