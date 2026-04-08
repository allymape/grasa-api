<?php

namespace App\Http\Controllers\Api;

use App\Models\Country;
use App\Models\District;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends ApiController
{
    public function countries(): JsonResponse
    {
        $countries = Country::query()
            ->orderBy('name')
            ->get(['id', 'name', 'iso2', 'phone_code', 'flag', 'requires_region_district']);

        return $this->success([
            'items' => $countries->map(fn (Country $country): array => [
                'id' => $country->id,
                'name' => $country->name,
                'iso2' => $country->iso2,
                'phone_code' => $country->phone_code,
                'flag' => $country->flag,
                'requires_region_district' => (bool) $country->requires_region_district,
            ])->all(),
        ], 'Countries retrieved.');
    }

    public function regions(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'country_iso2' => ['nullable', 'string', 'size:2'],
        ]);

        $countryId = $validated['country_id'] ?? null;
        if (! $countryId && ! empty($validated['country_iso2'])) {
            $countryId = Country::query()
                ->where('iso2', strtoupper((string) $validated['country_iso2']))
                ->value('id');
        }

        $regions = Region::query()
            ->with('country:id,name,iso2,phone_code,flag')
            ->when($countryId, fn ($query) => $query->where('country_id', (int) $countryId))
            ->orderBy('name')
            ->get(['id', 'country_id', 'name', 'code']);

        return $this->success([
            'items' => $regions->map(fn (Region $region): array => [
                'id' => $region->id,
                'country_id' => $region->country_id,
                'name' => $region->name,
                'code' => $region->code,
                'country' => $region->country ? [
                    'id' => $region->country->id,
                    'name' => $region->country->name,
                    'iso2' => $region->country->iso2,
                    'phone_code' => $region->country->phone_code,
                    'flag' => $region->country->flag,
                ] : null,
            ])->all(),
        ], 'Regions retrieved.');
    }

    public function districts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
        ]);

        $districts = District::query()
            ->with('region:id,country_id,name,code')
            ->when(
                isset($validated['region_id']),
                fn ($query) => $query->where('region_id', (int) $validated['region_id'])
            )
            ->when(
                isset($validated['country_id']),
                fn ($query) => $query->whereHas(
                    'region',
                    fn ($regionQuery) => $regionQuery->where('country_id', (int) $validated['country_id'])
                )
            )
            ->orderBy('name')
            ->get(['id', 'region_id', 'name', 'code']);

        return $this->success([
            'items' => $districts->map(fn (District $district): array => [
                'id' => $district->id,
                'region_id' => $district->region_id,
                'name' => $district->name,
                'code' => $district->code,
                'region' => $district->region ? [
                    'id' => $district->region->id,
                    'country_id' => $district->region->country_id,
                    'name' => $district->region->name,
                    'code' => $district->region->code,
                ] : null,
            ])->all(),
        ], 'Districts retrieved.');
    }
}
