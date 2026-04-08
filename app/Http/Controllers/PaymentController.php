<?php

namespace App\Http\Controllers;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentStatus;
use App\Http\Requests\StorePaymentRequest;
use App\Models\ConnectionRequest;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::query()
            ->with(['connectionRequest.receiver.profile', 'confirmer'])
            ->where('payer_id', auth()->id())
            ->latest('id')
            ->paginate(10);

        $pendingPaymentRequests = ConnectionRequest::query()
            ->with('receiver.profile')
            ->where('sender_id', auth()->id())
            ->where('status', ConnectionRequestStatus::PaymentPending->value)
            ->whereDoesntHave('payments', fn ($query) => $query->where('payer_id', auth()->id()))
            ->latest('id')
            ->get();

        return view('payments.index', compact('payments', 'pendingPaymentRequests'));
    }

    public function store(StorePaymentRequest $request, ConnectionRequest $connectionRequest): RedirectResponse
    {
        if ($connectionRequest->sender_id !== $request->user()->id) {
            abort(403, 'Only the sender can submit payment details.');
        }

        if ($connectionRequest->status !== ConnectionRequestStatus::PaymentPending) {
            return Redirect::back()->withErrors(['payment' => 'Payment can only be submitted for payment-pending requests.']);
        }

        $connectionRequest->payments()->updateOrCreate(
            [
                'connection_request_id' => $connectionRequest->id,
                'payer_id' => $request->user()->id,
            ],
            [
                'amount' => $request->validated('amount'),
                'method' => $request->validated('method'),
                'reference' => $request->validated('reference'),
                'status' => PaymentStatus::Pending->value,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]
        );

        return Redirect::route('payments.index')->with('status', 'payment-submitted');
    }
}
