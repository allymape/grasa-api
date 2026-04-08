<?php

namespace App\Http\Controllers;

use App\Enums\ConnectionRequestStatus;
use App\Enums\ProfileApprovalStatus;
use App\Http\Requests\RespondConnectionRequestRequest;
use App\Http\Requests\SendConnectionRequestRequest;
use App\Models\ConnectionRequest;
use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ConnectionRequestController extends Controller
{
    public function sent(): View
    {
        $requests = ConnectionRequest::query()
            ->with(['receiver.profile.photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
            ->where('sender_id', auth()->id())
            ->latest('id')
            ->paginate(10);

        return view('requests.sent', compact('requests'));
    }

    public function received(): View
    {
        $requests = ConnectionRequest::query()
            ->with(['sender.profile.photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')])
            ->where('receiver_id', auth()->id())
            ->latest('id')
            ->paginate(10);

        return view('requests.received', compact('requests'));
    }

    public function store(SendConnectionRequestRequest $request, Profile $profile): RedirectResponse
    {
        $senderId = (int) $request->user()->id;
        $receiverId = (int) $profile->user_id;

        if ($senderId === $receiverId) {
            return Redirect::back()->withErrors(['request' => 'You cannot request your own profile.']);
        }

        if (
            $profile->approval_status !== ProfileApprovalStatus::Approved
            || ! $profile->is_visible
        ) {
            return Redirect::back()->withErrors(['request' => 'This profile is not available for requests.']);
        }

        $hasActiveRequest = ConnectionRequest::query()
            ->active()
            ->where(function ($query) use ($senderId, $receiverId) {
                $query
                    ->where(function ($forward) use ($senderId, $receiverId) {
                        $forward->where('sender_id', $senderId)->where('receiver_id', $receiverId);
                    })
                    ->orWhere(function ($reverse) use ($senderId, $receiverId) {
                        $reverse->where('sender_id', $receiverId)->where('receiver_id', $senderId);
                    });
            })
            ->exists();

        if ($hasActiveRequest) {
            return Redirect::back()->withErrors(['request' => 'There is already an active request between you and this user.']);
        }

        ConnectionRequest::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'status' => ConnectionRequestStatus::Pending->value,
        ]);

        return Redirect::back()->with('status', 'request-sent');
    }

    public function accept(
        RespondConnectionRequestRequest $request,
        ConnectionRequest $connectionRequest
    ): RedirectResponse {
        if ($connectionRequest->receiver_id !== $request->user()->id) {
            abort(403, 'You cannot respond to this request.');
        }

        if ($connectionRequest->status !== ConnectionRequestStatus::Pending) {
            return Redirect::back()->withErrors(['request' => 'Only pending requests can be accepted.']);
        }

        $connectionRequest->update([
            'status' => ConnectionRequestStatus::PaymentPending->value,
            'responded_at' => Carbon::now(),
        ]);

        return Redirect::back()->with('status', 'request-accepted');
    }

    public function reject(
        RespondConnectionRequestRequest $request,
        ConnectionRequest $connectionRequest
    ): RedirectResponse {
        if ($connectionRequest->receiver_id !== $request->user()->id) {
            abort(403, 'You cannot respond to this request.');
        }

        if ($connectionRequest->status !== ConnectionRequestStatus::Pending) {
            return Redirect::back()->withErrors(['request' => 'Only pending requests can be rejected.']);
        }

        $connectionRequest->update([
            'status' => ConnectionRequestStatus::Rejected->value,
            'responded_at' => Carbon::now(),
        ]);

        return Redirect::back()->with('status', 'request-rejected');
    }
}
