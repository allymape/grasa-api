<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ProfileApprovalStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\Profile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminProfileController extends ApiController
{
    public function pending(Request $request): JsonResponse
    {
        $profiles = Profile::query()
            ->with([
                'user:id,first_name,last_name,phone,email,gender',
                'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
                'country:id,name,iso2,phone_code,flag',
                'region:id,country_id,name,code',
                'district:id,region_id,name,code',
            ])
            ->where('approval_status', ProfileApprovalStatus::Pending->value)
            ->latest('id')
            ->paginate(min(max((int) $request->integer('per_page', 12), 1), 50));

        return $this->success([
            'items' => $profiles->getCollection()->map(fn (Profile $profile): array => $this->formatProfile($profile))->all(),
            'meta' => [
                'current_page' => $profiles->currentPage(),
                'last_page' => $profiles->lastPage(),
                'per_page' => $profiles->perPage(),
                'total' => $profiles->total(),
            ],
        ], 'Pending profiles retrieved.');
    }

    public function approve(Request $request, Profile $profile): JsonResponse
    {
        $profile->update([
            'approval_status' => ProfileApprovalStatus::Approved->value,
            'is_visible' => true,
            'approved_by' => $request->user()->id,
            'approved_at' => Carbon::now(),
        ]);

        $profile->load([
            'user:id,first_name,last_name,phone,email,gender',
            'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
            'country:id,name,iso2,phone_code,flag',
            'region:id,country_id,name,code',
            'district:id,region_id,name,code',
        ]);

        return $this->success($this->formatProfile($profile), 'Profile approved.');
    }

    public function reject(Request $request, Profile $profile): JsonResponse
    {
        $profile->update([
            'approval_status' => ProfileApprovalStatus::Rejected->value,
            'is_visible' => false,
            'approved_by' => $request->user()->id,
            'approved_at' => Carbon::now(),
        ]);

        $profile->load([
            'user:id,first_name,last_name,phone,email,gender',
            'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
            'country:id,name,iso2,phone_code,flag',
            'region:id,country_id,name,code',
            'district:id,region_id,name,code',
        ]);

        return $this->success($this->formatProfile($profile), 'Profile rejected.');
    }

    private function formatProfile(Profile $profile): array
    {
        return [
            'id' => $profile->id,
            'user_id' => $profile->user_id,
            'display_name' => $profile->display_name,
            'age' => $profile->age,
            'date_of_birth' => $profile->date_of_birth?->toDateString(),
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
            'approval_status' => $profile->approval_status?->value ?? $profile->approval_status,
            'is_visible' => (bool) $profile->is_visible,
            'approved_by' => $profile->approved_by,
            'approved_at' => $profile->approved_at,
            'user' => $profile->relationLoaded('user') && $profile->user ? [
                'id' => $profile->user->id,
                'first_name' => $profile->user->first_name,
                'last_name' => $profile->user->last_name,
                'phone' => $profile->user->phone,
                'email' => $profile->user->email,
                'gender' => $profile->user->gender?->value ?? $profile->user->gender,
            ] : null,
            'photos' => $profile->relationLoaded('photos')
                ? $profile->photos->map(fn ($photo): array => [
                    'id' => $photo->id,
                    'path' => "/api/profile-photos/{$photo->id}",
                    'is_primary' => (bool) $photo->is_primary,
                    'sort_order' => $photo->sort_order,
                ])->all()
                : [],
        ];
    }
}
