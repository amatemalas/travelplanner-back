<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_guest_gets_401_on_protected_route(): void
    {
        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(401)
            ->assertJson(['error' => true]);
    }

    public function test_guest_gets_401_on_trips_endpoint(): void
    {
        $response = $this->getJson('/api/trips');

        $response->assertStatus(401)
            ->assertJson(['error' => true]);
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email'],
                'token',
                'message',
                'error',
            ])
            ->assertJson(['error' => false]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => true]);
    }

    public function test_authenticated_user_can_access_me(): void
    {
        $user = $this->authenticate();

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_user_can_logout(): void
    {
        $user = $this->authenticate();
        $this->assertNotNull($user->fresh()->api_token);

        $response = $this->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['error' => false]);

        $this->assertNull($user->fresh()->api_token);
    }

    public function test_token_invalidated_after_logout(): void
    {
        $user = $this->authenticate();
        $token = $user->api_token;

        $this->postJson('/api/auth/logout');

        $response = $this->withToken($token)->getJson('/api/auth/me');

        $response->assertStatus(401);
    }

    public function test_login_regenerates_token(): void
    {
        $oldToken = Str::random(80);

        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'api_token' => $oldToken,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $newToken = $response->json('token');

        $this->assertNotEquals($oldToken, $newToken);
    }
}
