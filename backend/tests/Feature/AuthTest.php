<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful user registration.
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'message' => 'Registration successful',
                'user' => [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
        ]);
    }

    /**
     * Test registration with duplicate email fails.
     */
    public function test_registration_with_duplicate_email_fails(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
        ]);

        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test registration with invalid password fails.
     */
    public function test_registration_with_invalid_password_fails(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test registration with missing fields fails.
     */
    public function test_registration_with_missing_fields_fails(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'John Doe',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test successful user login.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    /**
     * Test login with invalid credentials fails.
     */
    public function test_login_with_invalid_credentials_fails(): void
    {
        User::factory()->create([
            'email' => 'john@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'WrongPassword!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test login with non-existent email fails.
     */
    public function test_login_with_nonexistent_email_fails(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test login with missing fields fails.
     */
    public function test_login_with_missing_fields_fails(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test authenticated user can retrieve their profile.
     */
    public function test_authenticated_user_can_retrieve_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'user' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJson([
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    /**
     * Test unauthenticated user cannot retrieve profile.
     */
    public function test_unauthenticated_user_cannot_retrieve_profile(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can logout.
     */
    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test'])
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Logout successful',
            ]);
    }

    /**
     * Test unauthenticated user cannot logout.
     */
    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    /**
     * Test password is not exposed in user responses.
     */
    public function test_password_is_not_exposed_in_user_responses(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['_token' => 'test'])
            ->getJson('/api/v1/auth/me');

        $response->assertStatus(200)
            ->assertJsonMissing([
                'password',
            ]);

        $responseData = $response->json();
        $this->assertArrayNotHasKey('password', $responseData['user']);
    }
}
