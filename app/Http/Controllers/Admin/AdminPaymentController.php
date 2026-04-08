<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminPaymentController extends Controller
{
    public function index(): View
    {
        $payments = Payment::query()
            ->with(['payer', 'connectionRequest.sender.profile', 'connectionRequest.receiver.profile', 'confirmer'])
            ->latest('id')
            ->paginate(15);

        return view('admin.payments.index', compact('payments'));
    }

    public function confirm(Payment $payment): RedirectResponse
    {
        $payment->update([
            'status' => PaymentStatus::Confirmed->value,
            'confirmed_by' => auth()->id(),
            'confirmed_at' => Carbon::now(),
        ]);

        $connectionRequest = $payment->connectionRequest;
        if ($connectionRequest) {
            $confirmedCount = $connectionRequest->payments()
                ->where('status', PaymentStatus::Confirmed->value)
                ->count();

            if ($confirmedCount >= 2) {
                $connectionRequest->update([
                    'status' => ConnectionRequestStatus::Connected->value,
                    'connected_at' => Carbon::now(),
                ]);
            } else {
                $connectionRequest->update([
                    'status' => ConnectionRequestStatus::PartiallyPaid->value,
                    'connected_at' => null,
                ]);
            }
        }

        return back()->with('status', 'payment-confirmed');
    }

    public function reject(Payment $payment): RedirectResponse
    {
        $payment->update([
            'status' => PaymentStatus::Failed->value,
            'confirmed_by' => auth()->id(),
            'confirmed_at' => Carbon::now(),
        ]);

        $connectionRequest = $payment->connectionRequest;
        if ($connectionRequest) {
            $confirmedCount = $connectionRequest->payments()
                ->where('status', PaymentStatus::Confirmed->value)
                ->count();

            $connectionRequest->update([
                'status' => $confirmedCount >= 1
                    ? ConnectionRequestStatus::PartiallyPaid->value
                    : ConnectionRequestStatus::PaymentPending->value,
                'connected_at' => null,
            ]);
        }

        return back()->with('status', 'payment-rejected');
    }
}
