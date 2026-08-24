<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_tourist_role_only()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '081234567890',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => UserRole::TOURIST->value,
        ]);
    }

    public function test_registration_cannot_set_admin_role()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Admin Hacker',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'role' => UserRole::TOURIST->value,
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);
    }

    public function test_registration_creates_wallet_automatically()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Wallet Test',
            'email' => 'wallet@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);
        $user = User::where('email', 'wallet@example.com')->first();
        $this->assertNotNull($user->wallet);
        $this->assertEquals(0, $user->wallet->balance);
    }

    public function test_registration_cannot_set_umkm_role()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'UMKM Hacker',
            'email' => 'umkm@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'umkm',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'umkm@example.com',
            'role' => UserRole::TOURIST->value,
        ]);
    }

    public function test_registration_cannot_set_manager_role()
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Manager Hacker',
            'email' => 'manager@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'manager',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'email' => 'manager@example.com',
            'role' => UserRole::TOURIST->value,
        ]);
    }

    public function test_login_succeeds_with_correct_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => UserRole::TOURIST,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user']);
    }

    public function test_login_fails_with_incorrect_password()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }
}
