<?php

namespace App\Http\Controllers;

use App\Enums\ConnectionRequestStatus;
use App\Enums\ProfileApprovalStatus;
use App\Models\ConnectionRequest;
use App\Models\Profile;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user = Auth::user();
        $user->load(['profile', 'partnerPreference']);

        $matchesCount = Profile::query()
            ->where('user_id', '!=', $user->id)
            ->where('approval_status', ProfileApprovalStatus::Approved->value)
            ->where('is_visible', true)
            ->count();

        $sentCount = ConnectionRequest::query()
            ->where('sender_id', $user->id)
            ->count();

        $receivedPendingCount = ConnectionRequest::query()
            ->where('receiver_id', $user->id)
            ->where('status', ConnectionRequestStatus::Pending->value)
            ->count();

        return view('dashboard', compact('user', 'matchesCount', 'sentCount', 'receivedPendingCount'));
    }
}
