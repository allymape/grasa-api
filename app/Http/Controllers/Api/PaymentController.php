<?php

namespace App\Http\Controllers\Api;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\ConnectionRequest;
use App\Models\Payment;
use App\Services\ConnectionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PaymentController extends ApiController
{
    public function __construct(
        private readonly ConnectionPaymentService $paymentFlow
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $payments = Payment::query()
            ->with([
                'payer:id,first_name,last_name',
                'confirmer:id,first_name,last_name',
                'connectionRequest:id,sender_id,receiver_id,status,connected_at',
                'connectionRequest.sender:id,first_name,last_name',
                'connectionRequest.receiver:id,first_name,last_name',
                'connectionRequest.payments:id,connection_request_id,payer_id,status,reference,method,confirmed_at',
            ])
            ->where('payer_id', $request->user()->id)
            ->latest('id')
            ->paginate(min(max((int) $request->integer('per_page', 12), 1), 50));

        return $this->success([
            'pricing' => [
                'connection_fee_amount' => $this->paymentFlow->getConnectionFeeAmount(),
            ],
            'items' => $payments->getCollection()->map(
                fn (Payment $payment): array => $this->formatPayment($payment, (int) $request->user()->id)
            )->all(),
            'meta' => [
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
                'per_page' => $payments->perPage(),
                'total' => $payments->total(),
            ],
        ], 'Payments retrieved.');
    }

    public function store(Request $request, ConnectionRequest $connectionRequest): JsonResponse
    {
        $validated = $request->validate([
            'method' => ['required', Rule::in(array_column(PaymentMethod::cases(), 'value'))],
            'reference' => [
                'required',
                'string',
                'max:100',
            ],
        ]);

        $viewerId = (int) $request->user()->id;
        if (! in_array($viewerId, [(int) $connectionRequest->sender_id, (int) $connectionRequest->receiver_id], true)) {
            return $this->error('Only matched users can submit payment for this request.', 403);
        }

        $status = $connectionRequest->status?->value ?? (string) $connectionRequest->status;
        if (! in_array($status, ConnectionRequestStatus::awaitingPaymentValues(), true)) {
            return $this->error('Payment can only be submitted for payment-pending requests.');
        }

        $existingPayment = Payment::query()
            ->where('connection_request_id', $connectionRequest->id)
            ->where('payer_id', $viewerId)
            ->first();

        if ($existingPayment?->status === PaymentStatus::Confirmed) {
            return $this->error('Your payment is already confirmed for this request.');
        }

        $normalizedReference = strtoupper(trim((string) $validated['reference']));
        $duplicateReferenceQuery = Payment::query()
            ->where('reference', $normalizedReference);

        if ($existingPayment) {
            $duplicateReferenceQuery->where('id', '!=', $existingPayment->id);
        }

        if ($duplicateReferenceQuery->exists()) {
            return $this->error('The reference has already been used.', 422, [
                'reference' => ['The reference has already been used.'],
            ]);
        }

        $payment = Payment::query()->updateOrCreate(
            [
                'connection_request_id' => $connectionRequest->id,
                'payer_id' => $viewerId,
            ],
            [
                'amount' => $this->paymentFlow->getConnectionFeeAmount(),
                'method' => $validated['method'],
                'reference' => $normalizedReference,
                'status' => PaymentStatus::Pending->value,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]
        );

        $connectionRequest->load('payments');
        $this->paymentFlow->syncConnectionStatus($connectionRequest);

        $payment->load([
            'payer:id,first_name,last_name',
            'connectionRequest.sender:id,first_name,last_name',
            'connectionRequest.receiver:id,first_name,last_name',
            'connectionRequest.payments:id,connection_request_id,payer_id,status,reference,method,confirmed_at',
        ]);

        return $this->success(
            $this->formatPayment($payment, $viewerId),
            'Payment submitted.',
            201
        );
    }

    public function confirm(Request $request, Payment $payment): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return $this->error('Only admins can confirm payments.', 403);
        }

        if ($payment->status !== PaymentStatus::Pending) {
            if ($payment->status === PaymentStatus::Confirmed) {
                return $this->error('Payment is already confirmed.');
            }

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
            'payer:id,first_name,last_name',
            'confirmer:id,first_name,last_name',
            'connectionRequest.sender:id,first_name,last_name',
            'connectionRequest.receiver:id,first_name,last_name',
            'connectionRequest.payments:id,connection_request_id,payer_id,status,reference,method,confirmed_at',
        ]);

        return $this->success($this->formatPayment($payment, (int) $payment->payer_id), 'Payment confirmed.');
    }

    public function reject(Request $request, Payment $payment): JsonResponse
    {
        if (! $request->user()->is_admin) {
            return $this->error('Only admins can reject payments.', 403);
        }

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
            'payer:id,first_name,last_name',
            'confirmer:id,first_name,last_name',
            'connectionRequest.sender:id,first_name,last_name',
            'connectionRequest.receiver:id,first_name,last_name',
            'connectionRequest.payments:id,connection_request_id,payer_id,status,reference,method,confirmed_at',
        ]);

        return $this->success($this->formatPayment($payment, (int) $payment->payer_id), 'Payment rejected.');
    }

    private function formatPayment(Payment $payment, int $viewerId): array
    {
        return [
            'id' => $payment->id,
            'connection_request_id' => $payment->connection_request_id,
            'amount' => (string) $payment->amount,
            'method' => $payment->method?->value ?? $payment->method,
            'reference' => $payment->reference,
            'status' => $payment->status?->value ?? $payment->status,
            'payer' => $payment->relationLoaded('payer') && $payment->payer ? [
                'id' => $payment->payer->id,
                'name' => $payment->payer->full_name,
            ] : null,
            'confirmer' => $payment->relationLoaded('confirmer') && $payment->confirmer ? [
                'id' => $payment->confirmer->id,
                'name' => $payment->confirmer->full_name,
            ] : null,
            'connection_request' => $payment->relationLoaded('connectionRequest') && $payment->connectionRequest ? [
                'id' => $payment->connectionRequest->id,
                'status' => $payment->connectionRequest->status?->value ?? $payment->connectionRequest->status,
                'sender' => $payment->connectionRequest->relationLoaded('sender') && $payment->connectionRequest->sender ? [
                    'id' => $payment->connectionRequest->sender->id,
                    'name' => $payment->connectionRequest->sender->full_name,
                ] : null,
                'receiver' => $payment->connectionRequest->relationLoaded('receiver') && $payment->connectionRequest->receiver ? [
                    'id' => $payment->connectionRequest->receiver->id,
                    'name' => $payment->connectionRequest->receiver->full_name,
                ] : null,
                'payment_summary' => $this->paymentFlow->paymentSummary($payment->connectionRequest, $viewerId),
            ] : null,
            'confirmed_at' => $payment->confirmed_at,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
        ];
    }
}
