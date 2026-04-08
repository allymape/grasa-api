<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\UploadProfilePhotoRequest;
use App\Models\ProfilePhoto;
use App\Services\ProfileVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfilePhotoController extends ApiController
{
    public function __construct(
        private readonly ProfileVisibilityService $visibility
    ) {
    }

    public function store(UploadProfilePhotoRequest $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile()->first();

        if (! $profile) {
            return $this->error('Create your profile before uploading photos.', 422);
        }

        $existingCount = $profile->photos()->count();
        if ($existingCount >= 2) {
            return $this->error('You can upload a maximum of 2 photos.', 422);
        }

        /** @var \Illuminate\Http\UploadedFile $file */
        $file = $request->file('photo');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $filename = (string) Str::uuid().($extension !== '' ? ".{$extension}" : '');
        $storagePath = "profile-photos/{$user->id}/{$filename}";

        Storage::disk('local')->putFileAs("profile-photos/{$user->id}", $file, $filename);

        $photo = DB::transaction(function () use ($profile, $user, $request, $storagePath): ProfilePhoto {
            $setAsPrimary = (bool) $request->boolean('is_primary') || ! $profile->photos()->exists();

            if ($setAsPrimary) {
                $profile->photos()->update(['is_primary' => false]);
            }

            /** @var ProfilePhoto $photo */
            $photo = $profile->photos()->create([
                'user_id' => $user->id,
                'path' => $storagePath,
                'is_primary' => $setAsPrimary,
                'sort_order' => $profile->photos()->max('sort_order') + 1,
            ]);

            return $photo;
        });

        return $this->success($this->formatPhoto($photo), 'Photo uploaded.', 201);
    }

    public function destroy(Request $request, ProfilePhoto $profilePhoto): JsonResponse
    {
        if ($profilePhoto->user_id !== $request->user()->id) {
            return $this->error('You are not allowed to delete this photo.', 403);
        }

        $profile = $profilePhoto->profile()->first();
        $wasPrimary = (bool) $profilePhoto->is_primary;
        $storagePath = $profilePhoto->path;

        DB::transaction(function () use ($profilePhoto, $profile, $wasPrimary): void {
            $profilePhoto->delete();

            if ($wasPrimary && $profile) {
                $profile->photos()
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->first()?->update(['is_primary' => true]);
            }
        });

        if ($storagePath !== '' && Storage::disk('local')->exists($storagePath)) {
            Storage::disk('local')->delete($storagePath);
        }

        return $this->success(null, 'Photo removed.');
    }

    public function show(Request $request, ProfilePhoto $profilePhoto): BinaryFileResponse|JsonResponse
    {
        $profilePhoto->loadMissing('profile:id,user_id');

        if (! $this->visibility->canViewPhoto($request->user(), $profilePhoto)) {
            return $this->error('You are not authorized to view this photo.', 403);
        }

        if ($profilePhoto->path === '' || ! Storage::disk('local')->exists($profilePhoto->path)) {
            return $this->error('Photo not found.', 404);
        }

        $absolutePath = Storage::disk('local')->path($profilePhoto->path);

        return response()->file($absolutePath, [
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function formatPhoto(ProfilePhoto $photo): array
    {
        return [
            'id' => $photo->id,
            'path' => "/api/profile-photos/{$photo->id}",
            'is_primary' => (bool) $photo->is_primary,
            'sort_order' => (int) $photo->sort_order,
        ];
    }
}

