<?php

namespace Tests\Feature\Api\Concerns;

use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Enums\ProfileApprovalStatus;
use App\Enums\Religion;
use App\Models\Country;
use App\Models\District;
use App\Models\Profile;
use App\Models\Region;
use App\Models\User;
use Database\Seeders\CountrySeeder;
use Database\Seeders\TanzaniaLocationSeeder;

trait CreatesMatchmakingData
{
    /**
     * @return array{tz: Country, ke: Country, region: Region, district: District}
     */
    protected function seedLocationData(): array
    {
        $this->seed([
            CountrySeeder::class,
            TanzaniaLocationSeeder::class,
        ]);

        $tz = Country::query()->where('iso2', 'TZ')->firstOrFail();
        $ke = Country::query()->where('iso2', 'KE')->firstOrFail();
        $region = Region::query()->where('country_id', $tz->id)->firstOrFail();
        $district = District::query()->where('region_id', $region->id)->firstOrFail();

        return compact('tz', 'ke', 'region', 'district');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validProfilePayload(
        Country $country,
        ?Region $region = null,
        ?District $district = null,
        array $overrides = []
    ): array {
        return array_merge([
            'display_name' => 'Test Profile',
            'age' => 28,
            'country_id' => $country->id,
            'region_id' => $region?->id,
            'district_id' => $district?->id,
            'current_residence' => 'Dar es Salaam, Mikocheni',
            'height_cm' => 170,
            'employment_status' => EmploymentStatus::Employed->value,
            'job_title' => 'Engineer',
            'marital_status' => MaritalStatus::Single->value,
            'has_children' => false,
            'children_count' => 0,
            'religion' => Religion::Muslim->value,
            'body_type' => null,
            'skin_tone' => null,
            'about_me' => 'About me section',
            'life_outlook' => 'Life outlook section',
            'is_visible' => true,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function createProfileFor(
        User $user,
        Country $country,
        ?Region $region,
        ?District $district,
        array $overrides = []
    ): Profile {
        $base = [
            'user_id' => $user->id,
            'display_name' => $user->first_name.' Profile',
            'age' => 27,
            'country_id' => $country->id,
            'region_id' => $region?->id,
            'district_id' => $district?->id,
            'current_residence' => 'Current residence',
            'height_cm' => 168,
            'employment_status' => EmploymentStatus::Employed->value,
            'job_title' => 'Developer',
            'marital_status' => MaritalStatus::Single->value,
            'has_children' => false,
            'children_count' => 0,
            'religion' => Religion::Christian->value,
            'body_type' => null,
            'skin_tone' => null,
            'about_me' => 'About',
            'life_outlook' => 'Outlook',
            'approval_status' => ProfileApprovalStatus::Approved->value,
            'is_profile_complete' => true,
            'is_visible' => true,
        ];

        return Profile::query()->create(array_merge($base, $overrides));
    }
}
