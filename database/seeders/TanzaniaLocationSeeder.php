<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TanzaniaLocationSeeder extends Seeder
{
    public function run(): void
    {
        $tanzaniaId = DB::table('countries')
            ->where('iso2', 'TZ')
            ->value('id');

        if (! $tanzaniaId) {
            return;
        }

        $tanzaniaRegions = [
            ['name' => 'Arusha', 'code' => 'AR'],
            ['name' => 'Dar es Salaam', 'code' => 'DS'],
            ['name' => 'Dodoma', 'code' => 'DO'],
            ['name' => 'Geita', 'code' => 'GE'],
            ['name' => 'Iringa', 'code' => 'IR'],
            ['name' => 'Kagera', 'code' => 'KA'],
            ['name' => 'Katavi', 'code' => 'KT'],
            ['name' => 'Kigoma', 'code' => 'KG'],
            ['name' => 'Kilimanjaro', 'code' => 'KL'],
            ['name' => 'Lindi', 'code' => 'LI'],
            ['name' => 'Manyara', 'code' => 'MY'],
            ['name' => 'Mara', 'code' => 'MR'],
            ['name' => 'Mbeya', 'code' => 'MB'],
            ['name' => 'Morogoro', 'code' => 'MO'],
            ['name' => 'Mtwara', 'code' => 'MT'],
            ['name' => 'Mwanza', 'code' => 'MW'],
            ['name' => 'Njombe', 'code' => 'NJ'],
            ['name' => 'Pwani', 'code' => 'PW'],
            ['name' => 'Rukwa', 'code' => 'RK'],
            ['name' => 'Ruvuma', 'code' => 'RV'],
            ['name' => 'Shinyanga', 'code' => 'SH'],
            ['name' => 'Simiyu', 'code' => 'SI'],
            ['name' => 'Singida', 'code' => 'SG'],
            ['name' => 'Songwe', 'code' => 'SW'],
            ['name' => 'Tabora', 'code' => 'TB'],
            ['name' => 'Tanga', 'code' => 'TG'],
            ['name' => 'Urban West', 'code' => 'UW'],
            ['name' => 'North Unguja', 'code' => 'NU'],
            ['name' => 'South Unguja', 'code' => 'SU'],
            ['name' => 'North Pemba', 'code' => 'NP'],
            ['name' => 'South Pemba', 'code' => 'SP'],
        ];

        $now = now();
        $regionsPayload = array_map(fn (array $region): array => [
            'country_id' => $tanzaniaId,
            'name' => $region['name'],
            'code' => $region['code'],
            'created_at' => $now,
            'updated_at' => $now,
        ], $tanzaniaRegions);

        DB::table('regions')->upsert(
            $regionsPayload,
            ['country_id', 'name'],
            ['code', 'updated_at']
        );

        $regionIdsByCode = DB::table('regions')
            ->where('country_id', $tanzaniaId)
            ->pluck('id', 'code')
            ->all();

        $districtRows = [];
        foreach ($tanzaniaRegions as $region) {
            $regionId = $regionIdsByCode[$region['code']] ?? null;
            if (! $regionId) {
                continue;
            }

            $districtRows[] = [
                'region_id' => $regionId,
                'name' => 'Other',
                'code' => 'OTHER',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $darRegionId = $regionIdsByCode['DS'] ?? null;
        if ($darRegionId) {
            $districtRows = array_merge($districtRows, [
                [
                    'region_id' => $darRegionId,
                    'name' => 'Ilala',
                    'code' => 'ILALA',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'region_id' => $darRegionId,
                    'name' => 'Kinondoni',
                    'code' => 'KINONDONI',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'region_id' => $darRegionId,
                    'name' => 'Temeke',
                    'code' => 'TEMEKE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'region_id' => $darRegionId,
                    'name' => 'Ubungo',
                    'code' => 'UBUNGO',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                [
                    'region_id' => $darRegionId,
                    'name' => 'Kigamboni',
                    'code' => 'KIGAMBONI',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ]);
        }

        DB::table('districts')->upsert(
            $districtRows,
            ['region_id', 'name'],
            ['code', 'updated_at']
        );
    }
}
