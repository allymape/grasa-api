<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => SystemSettingService::CONNECTION_FEE_KEY],
            [
                'value' => SystemSettingService::DEFAULT_CONNECTION_FEE,
                'updated_by' => null,
            ]
        );

        SystemSetting::query()->updateOrCreate(
            ['key' => SystemSettingService::MALE_MIN_AGE_KEY],
            [
                'value' => (string) SystemSettingService::DEFAULT_MALE_MIN_AGE,
                'updated_by' => null,
            ]
        );

        SystemSetting::query()->updateOrCreate(
            ['key' => SystemSettingService::FEMALE_MIN_AGE_KEY],
            [
                'value' => (string) SystemSettingService::DEFAULT_FEMALE_MIN_AGE,
                'updated_by' => null,
            ]
        );
    }
}
