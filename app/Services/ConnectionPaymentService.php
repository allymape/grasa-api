<?php

namespace App\Services;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentStatus;
use App\Models\ConnectionRequest;
use App\Models\Payment;
use Illuminate\Support\Carbon;

class ConnectionPaymentService
{
    public function __construct(
        private readonly SystemSettingService $settings
    ) {
    }

    public function getConnectionFeeAmount(): string
    {
        return $this->settings->getConnectionFeeAmount();
    }

    /**
     * @return array{sender: Payment|null, receiver: Payment|null}
     */
    public function paymentsByParticipant(ConnectionRequest $request): array
    {
        $payments = $request->relationLoaded('payments')
            ? $request->payments
            : $request->payments()->get();

        /** @var Payment|null $senderPayment */
        $senderPayment = $payments->firstWhere('payer_id', $request->sender_id);
        /** @var Payment|null $receiverPayment */
        $receiverPayment = $payments->firstWhere('payer_id', $request->receiver_id);

        return [
            'sender' => $senderPayment,
            'receiver' => $receiverPayment,
        ];
    }

    public function computeStatus(ConnectionRequest $request): ConnectionRequestStatus
    {
        $participants = $this->paymentsByParticipant($request);
        $confirmedCount = collect($participants)
            ->filter(fn (?Payment $payment): bool => $payment?->status === PaymentStatus::Confirmed)
            ->count();

        if ($confirmedCount >= 2) {
            return ConnectionRequestStatus::Connected;
        }

        if ($confirmedCount === 1) {
            return ConnectionRequestStatus::PartiallyPaid;
        }

        return ConnectionRequestStatus::PaymentPending;
    }

    public function syncConnectionStatus(ConnectionRequest $request): ConnectionRequest
    {
        $nextStatus = $this->computeStatus($request);

        $updates = [
            'status' => $nextStatus->value,
            'connected_at' => $nextStatus === ConnectionRequestStatus::Connected
                ? ($request->connected_at ?? Carbon::now())
                : null,
        ];

        if (
            ($request->status?->value ?? (string) $request->status) !== $updates['status']
            || (bool) $request->connected_at !== ($updates['connected_at'] !== null)
        ) {
            $request->update($updates);
            $request->refresh();
        }

        return $request;
    }

    public function canUserSubmitPayment(ConnectionRequest $request, int $userId): bool
    {
        $status = $request->status?->value ?? (string) $request->status;
        if (! in_array($status, ConnectionRequestStatus::awaitingPaymentValues(), true)) {
            return false;
        }

        if (! in_array($userId, [(int) $request->sender_id, (int) $request->receiver_id], true)) {
            return false;
        }

        $payment = $request->payments()
            ->where('payer_id', $userId)
            ->latest('id')
            ->first();

        return $payment?->status !== PaymentStatus::Confirmed;
    }

    public function formatParticipantPayment(?Payment $payment): array
    {
        if (! $payment) {
            return [
                'payment_id' => null,
                'status' => 'not_submitted',
                'reference' => null,
                'method' => null,
                'confirmed_at' => null,
            ];
        }

        return [
            'payment_id' => $payment->id,
            'status' => $payment->status?->value ?? (string) $payment->status,
            'reference' => $payment->reference,
            'method' => $payment->method?->value ?? $payment->method,
            'confirmed_at' => $payment->confirmed_at,
        ];
    }

    public function paymentSummary(ConnectionRequest $request, int $viewerId): array
    {
        $participants = $this->paymentsByParticipant($request);

        $viewerIsSender = (int) $request->sender_id === $viewerId;
        $currentUserPayment = $viewerIsSender ? $participants['sender'] : $participants['receiver'];
        $counterpartPayment = $viewerIsSender ? $participants['receiver'] : $participants['sender'];

        $currentStatus = $currentUserPayment?->status?->value
            ?? ($currentUserPayment?->status ? (string) $currentUserPayment->status : 'not_submitted');
        $counterpartStatus = $counterpartPayment?->status?->value
            ?? ($counterpartPayment?->status ? (string) $counterpartPayment->status : 'not_submitted');

        return [
            'connection_fee_amount' => $this->getConnectionFeeAmount(),
            'current_user' => [
                ...$this->formatParticipantPayment($currentUserPayment),
                'can_submit' => $this->canUserSubmitPayment($request, $viewerId),
            ],
            'counterpart' => [
                ...$this->formatParticipantPayment($counterpartPayment),
                'has_confirmed' => $counterpartStatus === PaymentStatus::Confirmed->value,
            ],
            'both_confirmed' => $currentStatus === PaymentStatus::Confirmed->value
                && $counterpartStatus === PaymentStatus::Confirmed->value,
        ];
    }
}
