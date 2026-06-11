<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase; // Resets the database after every test run

    protected function setUp(): void
    {
        parent::setUp();
        // Force token expiration config to a predictable value for testing
        Config::set('sanctum.expiration', 60);
    }


    public function test_a_user_can_register_successfully()
    {
        $payload = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'country' => 'Brazil',
            'local_currency' => 'BRL'
        ];

        $response = $this->postJson('/api/auth/register', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'created_at'],
                'token',
                'type',
                'expires_in_minutes'
            ])
            ->assertJsonPath('user.email', 'john@example.com')
            ->assertJsonPath('expires_in_minutes', 60);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    }


    public function test_a_user_can_login_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email'    => 'jane@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $payload = [
            'email'    => 'jane@example.com',
            'password' => 'secret123',
        ];

        $response = $this->postJson('/api/auth/login', $payload);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email', 'created_at'],
                'token',
                'type',
                'expires_in_minutes'
            ])
            ->assertJsonPath('user.email', 'jane@example.com');
    }


    public function test_a_user_cannot_login_with_incorrect_credentials()
    {
        $user = User::factory()->create([
            'email'    => 'jane@example.com',
            'password' => bcrypt('secret123'),
        ]);

        $payload = [
            'email'    => 'jane@example.com',
            'password' => 'wrong-password',
        ];

        $response = $this->postJson('/api/auth/login', $payload);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid credentials.',
            ]);
    }


    public function test_logging_in_revokes_previous_tokens()
    {
        $user = User::factory()->create([
            'password' => bcrypt('secret123'),
        ]);

        // Create two legacy tokens simulated from older logins
        $user->createToken('old_token_1');
        $user->createToken('old_token_2');

        $this->assertEquals(2, $user->tokens()->count());

        // Log in again
        $this->postJson('/api/auth/login', [
            'email'    => $user->email,
            'password' => 'secret123',
        ]);

        // Total count should now be 1 (the brand new token, deleting the old 2)
        $this->assertEquals(1, $user->fresh()->tokens()->count());
    }


    public function test_an_authenticated_user_can_get_their_profile()
    {
        $user = User::factory()->create();
        
        // Authenticate the user via Sanctum actingAs helper
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'created_at'],
                'session_expires_in'
            ])
            ->assertJsonPath('user.id', $user->id);
    }


    public function test_a_user_can_logout()
    {
        $user = User::factory()->create();
        
        $token = $user->createToken('auth_token')->plainTextToken;
        $this->assertEquals(1, $user->tokens()->count());
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logged out successfully.']);

        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }


    public function test_a_user_can_refresh_their_token()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'token',
                'type',
                'expires_in_minutes'
            ])
            ->assertJsonPath('expires_in_minutes', 60);
    }


    public function test_unauthenticated_users_are_blocked_from_protected_routes()
    {
        // Testing that middleware works on protected routes
        $this->getJson('/api/auth/me')->assertStatus(401);
        $this->postJson('/api/auth/logout')->assertStatus(401);
        $this->postJson('/api/auth/refresh')->assertStatus(401);
    }

    // ── Registration Validation Tests ────────────────────────────────────────

    public function test_registration_requires_mandatory_fields()
    {
        $response = $this->postJson('/api/auth/register', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'email',
                'password',
                'country',
                'local_currency',
            ]);
    }

    public function test_registration_validates_email_format()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'not-an-email',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'country' => 'Brazil',
            'local_currency' => 'BRL'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_validates_email_uniqueness()
    {
        User::factory()->create(['email' => 'duplicate@example.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'duplicate@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'country' => 'Brazil',
            'local_currency' => 'BRL'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_registration_validates_password_confirmation()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Mismatched123',
            'country' => 'Brazil',
            'local_currency' => 'BRL'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_registration_validates_password_strength_requirements()
    {
        // Too short (less than 8)
        $responseShort = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Pas1',
            'password_confirmation' => 'Pas1',
            'country' => 'Brazil',
            'local_currency' => 'BRL'
        ]);
        $responseShort->assertStatus(422)->assertJsonValidationErrors(['password']);

        // Missing numbers
        $responseNoNumbers = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'PasswordNoNum',
            'password_confirmation' => 'PasswordNoNum',
            'country' => 'Brazil',
            'local_currency' => 'BRL'
        ]);
        $responseNoNumbers->assertStatus(422)->assertJsonValidationErrors(['password']);

        // Missing uppercase/lowercase mixed case
        $responseNoMixed = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'country' => 'Brazil',
            'local_currency' => 'BRL'
        ]);
        $responseNoMixed->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_registration_validates_currency_max_length()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'country' => 'Brazil',
            'local_currency' => 'BRLXX' // too long
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['local_currency']);
    }

    // ── Login Validation Tests ───────────────────────────────────────────────

    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_validates_email_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'not-an-email',
            'password' => 'password'
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }
}

