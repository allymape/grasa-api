<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\ConnectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminRequestController extends Controller
{
    public function index(): View
    {
        $requests = ConnectionRequest::query()
            ->with(['sender.profile', 'receiver.profile', 'payments'])
            ->latest('id')
            ->paginate(15);

        return view('admin.requests.index', compact('requests'));
    }

    public function markConnected(ConnectionRequest $connectionRequest): RedirectResponse
    {
        $confirmedPayments = $connectionRequest->payments()
            ->where('status', PaymentStatus::Confirmed->value)
            ->count();

        if ($confirmedPayments < 2) {
            return back()->withErrors(['request' => 'Both matched users must have confirmed payments before connection can be unlocked.']);
        }

        $connectionRequest->update([
            'status' => ConnectionRequestStatus::Connected->value,
            'connected_at' => Carbon::now(),
        ]);

        return back()->with('status', 'request-connected');
    }
}
