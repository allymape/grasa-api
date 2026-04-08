<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProfileApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminProfileController extends Controller
{
    public function pending(): View
    {
        $profiles = Profile::query()
            ->with(['user', 'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
            ->where('approval_status', ProfileApprovalStatus::Pending->value)
            ->latest('id')
            ->paginate(12);

        return view('admin.profiles.pending', compact('profiles'));
    }

    public function approved(): View
    {
        $profiles = Profile::query()
            ->with('user')
            ->where('approval_status', ProfileApprovalStatus::Approved->value)
            ->latest('id')
            ->paginate(12);

        return view('admin.profiles.approved', compact('profiles'));
    }

    public function approve(Profile $profile): RedirectResponse
    {
        $profile->update([
            'approval_status' => ProfileApprovalStatus::Approved->value,
            'is_visible' => true,
            'approved_by' => auth()->id(),
            'approved_at' => Carbon::now(),
        ]);

        return back()->with('status', 'profile-approved');
    }

    public function reject(Profile $profile): RedirectResponse
    {
        $profile->update([
            'approval_status' => ProfileApprovalStatus::Rejected->value,
            'is_visible' => false,
            'approved_by' => auth()->id(),
            'approved_at' => Carbon::now(),
        ]);

        return back()->with('status', 'profile-rejected');
    }

    public function users(): View
    {
        $users = User::query()
            ->with('profile')
            ->latest('id')
            ->paginate(20);

        return view('admin.users.index', compact('users'));
    }
}
