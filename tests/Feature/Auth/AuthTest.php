<?php

namespace Tests\Feature\Auth;

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Register
    // -------------------------------------------------------------------------

    public function test_register_creates_agency_and_owner(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'agency_name'           => 'Test Agency',
            'owner_name'            => 'Test Owner',
            'email'                 => 'test@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
            'phone'                 => '+998901234567',
            'country'              => 'UZ',
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'data' => ['access_token', 'token_type', 'expires_in', 'user'],
        ]);
        $this->assertEquals('test@example.com', $response->json('data.user.email'));
        $this->assertEquals('owner', $response->json('data.user.role'));
    }

    public function test_register_rejects_duplicate_email(): void
    {
        // Первая регистрация
        $this->postJson('/api/v1/auth/register', [
            'agency_name'           => 'Agency One',
            'owner_name'            => 'Owner One',
            'email'                 => 'dupe@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        // Вторая — должна упасть (email уже занят)
        $response = $this->postJson('/api/v1/auth/register', [
            'agency_name'           => 'Agency Two',
            'owner_name'            => 'Owner Two',
            'email'                 => 'dupe@example.com',
            'password'              => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422);
    }

    public function test_register_requires_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'agency_name' => 'Test',
            'owner_name'  => 'Test',
            'email'       => 'test@example.com',
            'password'    => 'Password123!',
            // no password_confirmation
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    public function test_register_requires_min_password_length(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'agency_name'           => 'Test',
            'owner_name'            => 'Test',
            'email'                 => 'test@example.com',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');
    }

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    public function test_login_returns_token(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner([], [
            'email'    => 'owner@test.com',
            'password' => bcrypt('Secret123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'owner@test.com',
            'password' => 'Secret123!',
        ]);

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['access_token', 'token_type', 'expires_in', 'user'],
        ]);
    }

    public function test_login_wrong_password_returns_422(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner([], [
            'email'    => 'owner@test.com',
            'password' => bcrypt('Correct123!'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'owner@test.com',
            'password' => 'WrongPass!',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_nonexistent_user_returns_422(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nobody@test.com',
            'password' => 'Whatever123!',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_inactive_user_returns_422(): void
    {
        [$agency, ] = $this->createAgencyWithOwner();
        $inactive = $this->createManager($agency, [
            'email'     => 'inactive@test.com',
            'password'  => bcrypt('Secret123!'),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'inactive@test.com',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(422);
    }

    // -------------------------------------------------------------------------
    // Me (protected route)
    // -------------------------------------------------------------------------

    public function test_me_returns_user_info(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();

        $response = $this->getJson('/api/v1/auth/me', $this->authHeaders($owner));

        $response->assertOk();
        $response->assertJsonPath('data.id', $owner->id);
        $response->assertJsonPath('data.role', 'owner');
        $response->assertJsonPath('data.agency_id', $agency->id);
    }

    public function test_me_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/v1/auth/me');

        $response->assertUnauthorized();
    }

    // -------------------------------------------------------------------------
    // Refresh
    // -------------------------------------------------------------------------

    public function test_refresh_returns_new_token(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();

        $response = $this->postJson('/api/v1/auth/refresh', [], $this->authHeaders($owner));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => ['access_token'],
        ]);
    }

    // -------------------------------------------------------------------------
    // Logout
    // -------------------------------------------------------------------------

    public function test_logout_returns_success(): void
    {
        [$agency, $owner] = $this->createAgencyWithOwner();
        $headers = $this->authHeaders($owner);

        $response = $this->postJson('/api/v1/auth/logout', [], $headers);
        $response->assertOk();
    }
}
