<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Api\Concerns\CreatesMatchmakingData;
use Tests\TestCase;

class LocationEndpointsTest extends TestCase
{
    use CreatesMatchmakingData;
    use RefreshDatabase;

    public function test_location_endpoints_are_public_and_return_country_metadata(): void
    {
        $this->seedLocationData();

        $this->getJson('/api/locations/countries')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'items' => [
                        '*' => ['id', 'name', 'iso2', 'phone_code', 'flag', 'requires_region_district'],
                    ],
                ],
            ]);

        $this->getJson('/api/locations/regions?country_iso2=TZ')
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
