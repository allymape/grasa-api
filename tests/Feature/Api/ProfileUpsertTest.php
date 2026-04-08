<?php

namespace Tests\Feature\Api;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Api\Concerns\CreatesMatchmakingData;
use Tests\TestCase;

class ProfileUpsertTest extends TestCase
{
    use CreatesMatchmakingData;
    use RefreshDatabase;

    public function test_profile_upsert_requires_region_and_district_for_tanzania(): void
    {
        $location = $this->seedLocationData();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = $this->validProfilePayload($location['tz'], null, null);

        $this->putJson('/api/profile', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['region_id', 'district_id']);
    }

    public function test_profile_upsert_rejects_region_country_mismatch(): void
    {
        $location = $this->seedLocationData();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $payload = $this->validProfilePayload(
            $location['ke'],
            $location['region'],
            $location['district']
        );

        $this->putJson('/api/profile', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['region_id']);
    }

    public function test_profile_upsert_enforces_children_consistency_and_height_range(): void
    {
        $location = $this->seedLocationData();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $invalidChildren = $this->validProfilePayload(
            $location['tz'],
            $location['region'],
            $location['district'],
            [
                'has_children' => true,
                'children_count' => 0,
            ]
        );

        $this->putJson('/api/profile', $invalidChildren)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['children_count']);

        $invalidHeight = $this->validProfilePayload(
            $location['tz'],
            $location['region'],
            $location['district'],
            ['height_cm' => 99]
        );

        $this->putJson('/api/profile', $invalidHeight)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['height_cm']);
    }

    public function test_profile_upsert_returns_country_flag_and_phone_code(): void
    {
        $location = $this->seedLocationData();
        $user = User::factory()->create([
            'phone' => '0712000011',
            'email' => 'profile-user@example.com',
        ]);
        Sanctum::actingAs($user);

        $payload = $this->validProfilePayload(
            $location['tz'],
            $location['region'],
            $location['district']
        );

        $response = $this->putJson('/api/profile', $payload)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.country.iso2', 'TZ')
            ->assertJsonPath('data.country.flag', '🇹🇿')
            ->assertJsonPath('data.country.phone_code', '+255');

        $profileId = (int) $response->json('data.id');
        $this->assertGreaterThan(0, $profileId);

        $this->assertDatabaseHas('profiles', [
            'id' => $profileId,
            'user_id' => $user->id,
            'country_id' => $location['tz']->id,
            'region_id' => $location['region']->id,
            'district_id' => $location['district']->id,
        ]);

        $this->assertNotNull(Profile::query()->find($profileId));
    }
}
