<?php

namespace App\Policies;

use App\Enums\ProfileApprovalStatus;
use App\Models\Profile;
use App\Models\User;

class ProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id
            || (
                $profile->approval_status === ProfileApprovalStatus::Approved
                && $profile->is_visible
            );
    }

    public function create(User $user): bool
    {
        return $user->profile()->doesntExist();
    }

    public function update(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id;
    }

    public function delete(User $user, Profile $profile): bool
    {
        return $user->id === $profile->user_id || $user->is_admin;
    }

    public function restore(User $user, Profile $profile): bool
    {
        return $user->is_admin;
    }

    public function forceDelete(User $user, Profile $profile): bool
    {
        return $user->is_admin;
    }
}
