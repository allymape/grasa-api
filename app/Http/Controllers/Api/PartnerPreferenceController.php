<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UpsertPartnerPreferenceRequest;
use App\Models\PartnerPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerPreferenceController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $preference = $request->user()->partnerPreference;

        if (! $preference) {
            return $this->success(null, 'Partner preferences not set yet.');
        }

        return $this->success($this->formatPreference($preference), 'Partner preferences retrieved.');
    }

    public function upsert(UpsertPartnerPreferenceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        foreach (
            [
                'must_have_job',
                'must_be_calm',
                'must_love_children',
                'must_be_modest',
                'must_be_respectful',
            ] as $field
        ) {
            $validated[$field] = (bool) ($validated[$field] ?? false);
        }

        $preference = $request->user()->partnerPreference()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return $this->success(
            $this->formatPreference($preference),
            'Partner preferences saved.'
        );
    }

    private function formatPreference(PartnerPreference $preference): array
    {
        return [
            'id' => $preference->id,
            'user_id' => $preference->user_id,
            'preferred_gender' => $preference->preferred_gender?->value ?? $preference->preferred_gender,
            'min_age' => $preference->min_age,
            'max_age' => $preference->max_age,
            'preferred_religion' => $preference->preferred_religion?->value ?? $preference->preferred_religion,
            'must_have_job' => (bool) $preference->must_have_job,
            'must_be_calm' => (bool) $preference->must_be_calm,
            'must_love_children' => (bool) $preference->must_love_children,
            'must_be_modest' => (bool) $preference->must_be_modest,
            'must_be_respectful' => (bool) $preference->must_be_respectful,
            'preferred_skin_tone' => $preference->preferred_skin_tone?->value ?? $preference->preferred_skin_tone,
            'preferred_body_type' => $preference->preferred_body_type?->value ?? $preference->preferred_body_type,
            'additional_notes' => $preference->additional_notes,
            'created_at' => $preference->created_at,
            'updated_at' => $preference->updated_at,
        ];
    }
}
