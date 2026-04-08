<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('countries')->upsert(
            [
                [
                    'name' => 'Tanzania',
                    'iso2' => 'TZ',
                    'phone_code' => '+255',
                    'flag' => '🇹🇿',
                    'requires_region_district' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Kenya',
                    'iso2' => 'KE',
                    'phone_code' => '+254',
                    'flag' => '🇰🇪',
                    'requires_region_district' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Uganda',
                    'iso2' => 'UG',
                    'phone_code' => '+256',
                    'flag' => '🇺🇬',
                    'requires_region_district' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Rwanda',
                    'iso2' => 'RW',
                    'phone_code' => '+250',
                    'flag' => '🇷🇼',
                    'requires_region_district' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Burundi',
                    'iso2' => 'BI',
                    'phone_code' => '+257',
                    'flag' => '🇧🇮',
                    'requires_region_district' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'name' => 'Democratic Republic of the Congo',
                    'iso2' => 'CD',
                    'phone_code' => '+243',
                    'flag' => '🇨🇩',
                    'requires_region_district' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ],
            ['iso2'],
            ['name', 'phone_code', 'flag', 'requires_region_district', 'updated_at']
        );
    }
}
