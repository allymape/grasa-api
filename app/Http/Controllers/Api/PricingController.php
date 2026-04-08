<?php

namespace App\Http\Controllers\Api;

use App\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;

class PricingController extends ApiController
{
    public function __construct(
        private readonly SystemSettingService $settings
    ) {
    }

    public function show(): JsonResponse
    {
        return $this->success([
            'connection_fee_amount' => $this->settings->getConnectionFeeAmount(),
        ], 'Pricing retrieved.');
    }
}
