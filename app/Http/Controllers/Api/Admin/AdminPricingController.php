<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Services\SystemSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminPricingController extends ApiController
{
    public function __construct(
        private readonly SystemSettingService $settings
    ) {
    }

    public function show(): JsonResponse
    {
        return $this->success([
            'connection_fee_amount' => $this->settings->getConnectionFeeAmount(),
        ], 'Pricing settings retrieved.');
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'connection_fee_amount' => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
        ]);

        $amount = $this->settings->setConnectionFeeAmount(
            (float) $validated['connection_fee_amount'],
            (int) $request->user()->id
        );

        return $this->success([
            'connection_fee_amount' => $amount,
        ], 'Pricing settings updated.');
    }
}
