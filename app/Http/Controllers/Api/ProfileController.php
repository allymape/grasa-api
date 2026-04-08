<?php

namespace App\Http\Controllers\Api;

use App\Enums\ProfileApprovalStatus;
use App\Http\Requests\Api\UpsertProfileRequest;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->profile()
            ->with([
                'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
                'country:id,name,iso2,phone_code,flag',
                'region:id,country_id,name,code',
                'district:id,region_id,name,code',
            ])
            ->first();

        if (! $profile) {
            return $this->success(null, 'Profile not created yet.');
        }

        return $this->success($this->formatProfile($profile, true), 'Profile retrieved.');
    }

    public function upsert(UpsertProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $validated['has_children'] = (bool) $validated['has_children'];
        $validated['children_count'] = $validated['has_children'] ? (int) $validated['children_count'] : 0;
        $validated['is_visible'] = (bool) ($validated['is_visible'] ?? true);

        $profile = DB::transaction(function () use ($user, $validated): Profile {
            $profileData = collect($validated)
                ->except('photos')
                ->merge([
                    'approval_status' => ProfileApprovalStatus::Pending->value,
                    'is_profile_complete' => true,
                    'approved_by' => null,
                    'approved_at' => null,
                ])
                ->all();

            $profile = $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );

            if ($profile->photos()->exists() && ! $profile->photos()->where('is_primary', true)->exists()) {
                $profile->photos()->oldest('id')->first()?->update(['is_primary' => true]);
            }

            return $profile->load([
                'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
                'country:id,name,iso2,phone_code,flag',
                'region:id,country_id,name,code',
                'district:id,region_id,name,code',
            ]);
        });

        return $this->success($this->formatProfile($profile, true), 'Profile saved.');
    }

    private function formatProfile(Profile $profile, bool $includeContact): array
    {
        $profile->loadMissing([
            'user',
            'photos',
            'country:id,name,iso2,phone_code,flag',
            'region:id,country_id,name,code',
            'district:id,region_id,name,code',
        ]);

        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'display_name' => $profile->display_name,
            'age' => $profile->age,
            'country_id' => $profile->country_id,
            'region_id' => $profile->region_id,
            'district_id' => $profile->district_id,
            'country' => $profile->country ? [
                'id' => $profile->country->id,
                'name' => $profile->country->name,
                'iso2' => $profile->country->iso2,
                'phone_code' => $profile->country->phone_code,
                'flag' => $profile->country->flag,
            ] : null,
            'region' => $profile->region ? [
                'id' => $profile->region->id,
                'name' => $profile->region->name,
                'code' => $profile->region->code,
            ] : null,
            'district' => $profile->district ? [
                'id' => $profile->district->id,
                'name' => $profile->district->name,
                'code' => $profile->district->code,
            ] : null,
            'current_residence' => $profile->current_residence,
            'height_cm' => $profile->height_cm,
            'employment_status' => $profile->employment_status?->value ?? $profile->employment_status,
            'job_title' => $profile->job_title,
            'marital_status' => $profile->marital_status?->value ?? $profile->marital_status,
            'has_children' => $profile->has_children,
            'children_count' => $profile->children_count,
            'religion' => $profile->religion?->value ?? $profile->religion,
            'body_type' => $profile->body_type?->value ?? $profile->body_type,
            'skin_tone' => $profile->skin_tone?->value ?? $profile->skin_tone,
            'about_me' => $profile->about_me,
            'life_outlook' => $profile->life_outlook,
            'approval_status' => $profile->approval_status?->value ?? $profile->approval_status,
            'is_profile_complete' => $profile->is_profile_complete,
            'is_visible' => $profile->is_visible,
            'photos' => $profile->photos
                ->sortBy([
                    ['is_primary', 'desc'],
                    ['sort_order', 'asc'],
                ])
                ->values()
                ->map(fn ($photo): array => [
                    'id' => $photo->id,
                    'path' => "/api/profile-photos/{$photo->id}",
                    'is_primary' => (bool) $photo->is_primary,
                    'sort_order' => $photo->sort_order,
                ])
                ->all(),
            'primary_photo' => $profile->photos
                ->sortBy([
                    ['is_primary', 'desc'],
                    ['sort_order', 'asc'],
                ])
                ->values()
                ->map(fn ($photo): array => [
                    'id' => $photo->id,
                    'path' => "/api/profile-photos/{$photo->id}",
                ])
                ->first(),
            'is_sensitive_unlocked' => true,
            'user' => [
                'id' => $profile->user->id,
                'first_name' => $profile->user->first_name,
                'last_name' => $profile->user->last_name,
                'gender' => $profile->user->gender?->value ?? $profile->user->gender,
                'phone' => $includeContact ? $profile->user->phone : null,
                'email' => $includeContact ? $profile->user->email : null,
            ],
            'created_at' => $profile->created_at,
            'updated_at' => $profile->updated_at,
        ];
    }
}
