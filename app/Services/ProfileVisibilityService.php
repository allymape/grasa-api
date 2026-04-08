<?php

namespace App\Services;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\ConnectionRequest;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\User;

class ProfileVisibilityService
{
    public function canViewSensitive(User $viewer, Profile $profile): bool
    {
        if ($viewer->is_admin || $viewer->id === $profile->user_id) {
            return true;
        }

        return $this->hasConfirmedConnectionBetween($viewer->id, $profile->user_id);
    }

    /**
     * @param  list<int>  $candidateUserIds
     * @return list<int>
     */
    public function connectedUserIds(User $viewer, array $candidateUserIds): array
    {
        if ($candidateUserIds === []) {
            return [];
        }

        if ($viewer->is_admin) {
            return array_values(array_unique($candidateUserIds));
        }

        $viewerId = (int) $viewer->id;

        $connections = ConnectionRequest::query()
            ->select(['sender_id', 'receiver_id'])
            ->where('status', ConnectionRequestStatus::Connected->value)
            ->whereHas(
                'payments',
                fn ($query) => $query->where('status', PaymentStatus::Confirmed->value),
                '>=',
                2
            )
            ->where(function ($query) use ($viewerId, $candidateUserIds): void {
                $query
                    ->where(function ($forward) use ($viewerId, $candidateUserIds): void {
                        $forward
                            ->where('sender_id', $viewerId)
                            ->whereIn('receiver_id', $candidateUserIds);
                    })
                    ->orWhere(function ($reverse) use ($viewerId, $candidateUserIds): void {
                        $reverse
                            ->where('receiver_id', $viewerId)
                            ->whereIn('sender_id', $candidateUserIds);
                    });
            })
            ->get();

        $unlockedUserIds = [];
        foreach ($connections as $connection) {
            $unlockedUserIds[] = (int) ($connection->sender_id === $viewerId
                ? $connection->receiver_id
                : $connection->sender_id);
        }

        return array_values(array_unique($unlockedUserIds));
    }

    public function canViewPhoto(User $viewer, ProfilePhoto $photo): bool
    {
        if ($viewer->is_admin || $viewer->id === $photo->user_id) {
            return true;
        }

        $profile = $photo->relationLoaded('profile')
            ? $photo->profile
            : $photo->profile()->first();

        if (! $profile) {
            return false;
        }

        return $this->hasConfirmedConnectionBetween($viewer->id, (int) $profile->user_id);
    }

    public function hasConfirmedConnectionBetween(int $firstUserId, int $secondUserId): bool
    {
        return ConnectionRequest::query()
            ->where('status', ConnectionRequestStatus::Connected->value)
            ->whereHas(
                'payments',
                fn ($query) => $query->where('status', PaymentStatus::Confirmed->value),
                '>=',
                2
            )
            ->where(function ($query) use ($firstUserId, $secondUserId): void {
                $query
                    ->where(function ($forward) use ($firstUserId, $secondUserId): void {
                        $forward->where('sender_id', $firstUserId)->where('receiver_id', $secondUserId);
                    })
                    ->orWhere(function ($reverse) use ($firstUserId, $secondUserId): void {
                        $reverse->where('sender_id', $secondUserId)->where('receiver_id', $firstUserId);
                    });
            })
            ->exists();
    }
}
