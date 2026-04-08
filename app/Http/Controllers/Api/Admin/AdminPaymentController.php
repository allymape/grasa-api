<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\Payment;
use App\Services\ConnectionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminPaymentController extends ApiController
{
    public function __construct(
        private readonly ConnectionPaymentService $paymentFlow
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with([
                'payer:id,first_name,last_name,phone,email',
                'confirmer:id,first_name,last_name',
                'connectionRequest:id,sender_id,receiver_id,status,connected_at',
                'connectionRequest.sender:id,first_name,last_name',
                'connectionRequest.receiver:id,first_name,last_name',
                'connectionRequest.payments:id,connection_request_id,payer_id,status,reference,method,confirmed_at',
            ])
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->input('status')))
            ->latest('id')
            ->paginate(min(max((int) $request->integer('per_page', 15), 1), 100));

        return $this->success([
            'pricing' => [
                'connection_fee_amount' => $this->paymentFlow->getConnectionFeeAmount(),
            ],
            'items' => $payments->getCollection()->map(
                fn (Payment $payment): array => $this->formatPayment($payment)
            )->all(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ], 'Payments retrieved.');
    }

    public function confirm(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status !== PaymentStatus::Pending) {
            return $this->error('Only pending payments can be confirmed.');
        }

        if (! $payment->connectionRequest) {
            return $this->error('Connection request not found for this payment.', 404);
        }

        $requestStatus = $payment->connectionRequest->status?->value ?? (string) $payment->connectionRequest->status;
        if (! in_array($requestStatus, ConnectionRequestStatus::awaitingPaymentValues(), true)) {
            return $this->error('Payment can only be confirmed while request is awaiting payment.');
        }

        $payment->update([
            'status' => PaymentStatus::Confirmed->value,
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => Carbon::now(),
        ]);

        $payment->connectionRequest->load('payments');
        $this->paymentFlow->syncConnectionStatus($payment->connectionRequest);

        $payment->load([
            'payer:id,first_name,last_name,phone,email',
            'confirmer:id,first_name,last_name',
            'connectionRequest:id,sender_id,receiver_id,status,connected_at',
            'connectionRequest.sender:id,first_name,last_name',
            'connectionRequest.receiver:id,first_name,last_name',
            'connectionRequest.payments:id,connection_request_id,payer_id,status,reference,method,confirmed_at',
        ]);

        return $this->success($this->formatPayment($payment), 'Payment confirmed.');
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->status === PaymentStatus::Confirmed) {
            return $this->error('Confirmed payments cannot be rejected.');
        }

        if ($payment->status === PaymentStatus::Failed) {
            return $this->error('Payment is already marked as failed.');
        }

        $payment->update([
            'status' => PaymentStatus::Failed->value,
            'confirmed_by' => $request->user()->id,
            'confirmed_at' => Carbon::now(),
        ]);

        $payment->connectionRequest?->load('payments');
        if ($payment->connectionRequest) {
            $this->paymentFlow->syncConnectionStatus($payment->connectionRequest);
        }

        $payment->load([
            'payer:id,first_name,last_name,phone,email',
            'confirmer:id,first_name,last_name',
            'connectionRequest:id,sender_id,receiver_id,status,connected_at',
            'connectionRequest.sender:id,first_name,last_name',
            'connectionRequest.receiver:id,first_name,last_name',
            'connectionRequest.payments:id,connection_request_id,payer_id,status,reference,method,confirmed_at',
        ]);

        return $this->success($this->formatPayment($payment), 'Payment rejected.');
    }

    private function formatPayment(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'connection_request_id' => $payment->connection_request_id,
            'amount' => (string) $payment->amount,
            'method' => $payment->method?->value ?? $payment->method,
            'reference' => $payment->reference,
            'status' => $payment->status?->value ?? $payment->status,
            'confirmed_at' => $payment->confirmed_at,
            'payer' => $payment->relationLoaded('payer') && $payment->payer ? [
                'id' => $payment->payer->id,
                'first_name' => $payment->payer->first_name,
                'last_name' => $payment->payer->last_name,
                'phone' => $payment->payer->phone,
                'email' => $payment->payer->email,
            ] : null,
            'confirmer' => $payment->relationLoaded('confirmer') && $payment->confirmer ? [
                'id' => $payment->confirmer->id,
                'first_name' => $payment->confirmer->first_name,
                'last_name' => $payment->confirmer->last_name,
            ] : null,
            'connection_request' => $payment->relationLoaded('connectionRequest') && $payment->connectionRequest ? [
                'id' => $payment->connectionRequest->id,
                'status' => $payment->connectionRequest->status?->value ?? $payment->connectionRequest->status,
                'connected_at' => $payment->connectionRequest->connected_at,
                'sender' => $payment->connectionRequest->relationLoaded('sender') && $payment->connectionRequest->sender ? [
                    'id' => $payment->connectionRequest->sender->id,
                    'first_name' => $payment->connectionRequest->sender->first_name,
                    'last_name' => $payment->connectionRequest->sender->last_name,
                ] : null,
                'receiver' => $payment->connectionRequest->relationLoaded('receiver') && $payment->connectionRequest->receiver ? [
                    'id' => $payment->connectionRequest->receiver->id,
                    'first_name' => $payment->connectionRequest->receiver->first_name,
                    'last_name' => $payment->connectionRequest->receiver->last_name,
                ] : null,
                'payment_summary' => $this->paymentFlow->paymentSummary($payment->connectionRequest, (int) $payment->payer_id),
            ] : null,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
        ];
    }
}
