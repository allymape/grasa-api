<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_via_api(): void
    {
        $response = $this->postJson('/api/register', [
            'first_name' => 'Amina',
            'last_name' => 'Juma',
            'phone' => '0711223344',
            'email' => 'amina@example.com',
            'gender' => 'female',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'token',
                    'token_type',
                    'user' => ['id', 'first_name', 'phone', 'gender'],
                ],
            ]);
    }

    public function test_user_can_login_fetch_profile_and_logout_via_api(): void
    {
        $user = User::factory()->create([
            'phone' => '0711000000',
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $loginResponse = $this->postJson('/api/login', [
            'login' => '0711000000',
            'password' => 'password123',
        ]);

        $loginResponse
            ->assertOk()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['token', 'token_type', 'user'],
            ]);

        $token = $loginResponse->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');
    }
}
