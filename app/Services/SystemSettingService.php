<?php

namespace App\Services;

use App\Models\SystemSetting;

class SystemSettingService
{
    public const CONNECTION_FEE_KEY = 'pricing.connection_fee_amount';
    public const DEFAULT_CONNECTION_FEE = '25000.00';

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
}
