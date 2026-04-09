<?php

namespace App\Services;

use App\Enums\Gender;
use App\Models\SystemSetting;

class SystemSettingService
{
    public const CONNECTION_FEE_KEY = 'pricing.connection_fee_amount';
    public const DEFAULT_CONNECTION_FEE = '25000.00';
    public const MALE_MIN_AGE_KEY = 'male_min_age';
    public const FEMALE_MIN_AGE_KEY = 'female_min_age';
    public const DEFAULT_MALE_MIN_AGE = 25;
    public const DEFAULT_FEMALE_MIN_AGE = 20;

    public function getConnectionFeeAmount(): string
    {
        $value = SystemSetting::query()
            ->where('key', self::CONNECTION_FEE_KEY)
            ->value('value');

        if (! is_string($value) || trim($value) === '') {
            return self::DEFAULT_CONNECTION_FEE;
        }

        return number_format((float) $value, 2, '.', '');
    }

    public function setConnectionFeeAmount(float $amount, int $adminId): string
    {
        $normalized = number_format($amount, 2, '.', '');

        SystemSetting::query()->updateOrCreate(
            ['key' => self::CONNECTION_FEE_KEY],
            ['value' => $normalized, 'updated_by' => $adminId]
        );

        return $normalized;
    }

    public function getMaleMinimumAge(): int
    {
        return $this->getIntegerSetting(self::MALE_MIN_AGE_KEY, self::DEFAULT_MALE_MIN_AGE);
    }

    public function setMaleMinimumAge(int $age, int $adminId): int
    {
        return $this->setIntegerSetting(self::MALE_MIN_AGE_KEY, $age, $adminId);
    }

    public function getFemaleMinimumAge(): int
    {
        return $this->getIntegerSetting(self::FEMALE_MIN_AGE_KEY, self::DEFAULT_FEMALE_MIN_AGE);
    }

    public function setFemaleMinimumAge(int $age, int $adminId): int
    {
        return $this->setIntegerSetting(self::FEMALE_MIN_AGE_KEY, $age, $adminId);
    }

    public function getMinimumAgeForGender(Gender|string|null $gender): int
    {
        $resolved = $gender instanceof Gender ? $gender->value : strtolower((string) $gender);

        return match ($resolved) {
            Gender::Male->value => $this->getMaleMinimumAge(),
            Gender::Female->value => $this->getFemaleMinimumAge(),
            default => max($this->getMaleMinimumAge(), $this->getFemaleMinimumAge()),
        };
    }

    /**
     * @return array{connection_fee_amount:string,male_min_age:int,female_min_age:int}
     */
    public function getAdminPricingSettings(): array
    {
        return [
            'connection_fee_amount' => $this->getConnectionFeeAmount(),
            'male_min_age' => $this->getMaleMinimumAge(),
            'female_min_age' => $this->getFemaleMinimumAge(),
        ];
    }

    private function getIntegerSetting(string $key, int $default): int
    {
        $value = SystemSetting::query()
            ->where('key', $key)
            ->value('value');

        if (! is_numeric($value)) {
            return $default;
        }

        return max(18, (int) $value);
    }

    private function setIntegerSetting(string $key, int $value, int $adminId): int
    {
        $normalized = max(18, $value);

        SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $normalized, 'updated_by' => $adminId]
        );

        return $normalized;
    }
}
