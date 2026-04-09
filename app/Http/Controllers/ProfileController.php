<?php

namespace App\Http\Controllers;

use App\Enums\ProfileApprovalStatus;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->profile()->with('photos')->first();

        return view('profile.edit', [
            'profile' => $profile,
        ]);
    }

    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        $dateOfBirth = isset($validated['date_of_birth']) && $validated['date_of_birth'] !== null
            ? Carbon::parse((string) $validated['date_of_birth'])->toDateString()
            : null;

        if (! $dateOfBirth && isset($validated['age'])) {
            $dateOfBirth = Carbon::today()->subYears((int) $validated['age'])->toDateString();
        }

        if ($dateOfBirth) {
            $validated['date_of_birth'] = $dateOfBirth;
            $validated['age'] = Carbon::parse($dateOfBirth)->age;
        }

        $validated['has_children'] = (bool) ($validated['has_children'] ?? false);
        $validated['children_count'] = $validated['has_children'] ? (int) $validated['children_count'] : 0;
        $validated['is_visible'] = (bool) ($validated['is_visible'] ?? true);
        $validated['is_profile_complete'] = true;
        $validated['approval_status'] = ProfileApprovalStatus::Pending->value;
        $validated['approved_by'] = null;
        $validated['approved_at'] = null;

        $profileData = collect($validated)->except(['photos', 'primary_photo_id'])->all();

        /** @var Profile $profile */
        $profile = DB::transaction(function () use ($user, $request, $profileData): Profile {
            $profile = $user->profile()->updateOrCreate(['user_id' => $user->id], $profileData);

            if ($request->hasFile('photos')) {
                $existingPhotoCount = $profile->photos()->count();

                foreach ($request->file('photos', []) as $index => $photoFile) {
                    $path = $photoFile->store('profile-photos', 'public');

                    $profile->photos()->create([
                        'user_id' => $user->id,
                        'path' => $path,
                        'is_primary' => false,
                        'sort_order' => $existingPhotoCount + $index + 1,
                    ]);
                }
            }

            $primaryPhotoId = $request->integer('primary_photo_id');

            if ($primaryPhotoId > 0) {
                $primaryPhoto = $profile->photos()->where('id', $primaryPhotoId)->first();

                if ($primaryPhoto) {
                    $profile->photos()->update(['is_primary' => false]);
                    $primaryPhoto->update(['is_primary' => true]);
                }
            } elseif (! $profile->photos()->where('is_primary', true)->exists()) {
                $profile->photos()->oldest('id')->first()?->update(['is_primary' => true]);
            }

            return $profile->load('photos');
        });

        $this->authorize('update', $profile);

        return Redirect::route('profile.edit')->with('status', 'profile-saved');
    }
}
