<?php

namespace App\Http\Controllers\Api;

use App\Enums\ConnectionRequestStatus;
use App\Enums\ProfileApprovalStatus;
use App\Http\Requests\Api\StoreConnectionRequestRequest;
use App\Models\ConnectionRequest;
use App\Models\Profile;
use App\Services\ConnectionPaymentService;
use App\Services\ProfileVisibilityService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ConnectionRequestController extends ApiController
{
    private const LOCKED_NAME = 'Private Member';

    public function __construct(
        private readonly ProfileVisibilityService $visibility,
        private readonly ConnectionPaymentService $paymentFlow
    ) {
    }

    public function sent(Request $request): JsonResponse
    {
        $requests = ConnectionRequest::query()
            ->with([
                'sender.profile',
                'receiver.profile.photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
                'payments:id,connection_request_id,payer_id,status,amount,method,reference,confirmed_at',
            ])
            ->where('sender_id', $request->user()->id)
            ->latest('id')
            ->paginate(min(max((int) $request->integer('per_page', 12), 1), 50));

        $counterpartIdMap = $this->buildCounterpartUnlockMap($request, $requests->getCollection());

        return $this->success([
            'pricing' => [
                'connection_fee_amount' => $this->paymentFlow->getConnectionFeeAmount(),
            ],
            'items' => $requests->getCollection()->map(
                fn (ConnectionRequest $item): array => $this->formatRequest(
                    $item,
                    (int) $request->user()->id,
                    isset($counterpartIdMap[$this->counterpartUserId($item, (int) $request->user()->id)])
                )
            )->all(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ], 'Sent requests retrieved.');
    }

    public function received(Request $request): JsonResponse
    {
        $requests = ConnectionRequest::query()
            ->with([
                'sender.profile.photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
                'receiver.profile',
                'payments:id,connection_request_id,payer_id,status,amount,method,reference,confirmed_at',
            ])
            ->where('receiver_id', $request->user()->id)
            ->latest('id')
            ->paginate(min(max((int) $request->integer('per_page', 12), 1), 50));

        $counterpartIdMap = $this->buildCounterpartUnlockMap($request, $requests->getCollection());

        return $this->success([
            'pricing' => [
                'connection_fee_amount' => $this->paymentFlow->getConnectionFeeAmount(),
            ],
            'items' => $requests->getCollection()->map(
                fn (ConnectionRequest $item): array => $this->formatRequest(
                    $item,
                    (int) $request->user()->id,
                    isset($counterpartIdMap[$this->counterpartUserId($item, (int) $request->user()->id)])
                )
            )->all(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ], 'Received requests retrieved.');
    }

    public function store(StoreConnectionRequestRequest $request): JsonResponse
    {
        $receiverProfile = Profile::query()
            ->whereKey($request->integer('receiver_profile_id'))
            ->first();

        if (! $receiverProfile) {
            return $this->error('Receiver profile not found.', 404);
        }

        if ($receiverProfile->user_id === $request->user()->id) {
            return $this->error('You cannot request your own profile.');
        }

        if (
            $receiverProfile->approval_status !== ProfileApprovalStatus::Approved
            || ! $receiverProfile->is_visible
        ) {
            return $this->error('This profile is not available for connection requests.');
        }

        $senderId = (int) $request->user()->id;
        $receiverId = (int) $receiverProfile->user_id;

        $hasActiveRequest = ConnectionRequest::query()
            ->active()
            ->where(function ($query) use ($senderId, $receiverId): void {
                $query
                    ->where(function ($forward) use ($senderId, $receiverId): void {
                        $forward->where('sender_id', $senderId)->where('receiver_id', $receiverId);
                    })
                    ->orWhere(function ($reverse) use ($senderId, $receiverId): void {
                        $reverse->where('sender_id', $receiverId)->where('receiver_id', $senderId);
                    });
            })
            ->exists();

        if ($hasActiveRequest) {
            return $this->error('An active request already exists between these users.');
        }

        try {
            $connectionRequest = ConnectionRequest::query()->create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'status' => ConnectionRequestStatus::Pending->value,
                'message' => $request->filled('message') ? (string) $request->input('message') : null,
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23000') {
                return $this->error('An active request already exists between these users.');
            }

            throw $exception;
        }

        $connectionRequest->load([
            'sender.profile',
            'receiver.profile',
            'payments:id,connection_request_id,payer_id,status,amount,method,reference,confirmed_at',
        ]);

        return $this->success(
            $this->formatRequest(
                $connectionRequest,
                $senderId,
                $this->visibility->hasConfirmedConnectionBetween(
                    (int) $connectionRequest->sender_id,
                    (int) $connectionRequest->receiver_id
                )
            ),
            'Connection request sent.',
            201
        );
    }

    public function accept(Request $request, ConnectionRequest $connectionRequest): JsonResponse
    {
        if ((int) $connectionRequest->receiver_id !== (int) $request->user()->id) {
            return $this->error('You are not allowed to accept this request.', 403);
        }

        if ($connectionRequest->status !== ConnectionRequestStatus::Pending) {
            return $this->error('Only pending requests can be accepted.');
        }

        $connectionRequest->update([
            'status' => ConnectionRequestStatus::PaymentPending->value,
            'responded_at' => Carbon::now(),
            'connected_at' => null,
        ]);

        $connectionRequest->load([
            'sender.profile',
            'receiver.profile',
            'payments:id,connection_request_id,payer_id,status,amount,method,reference,confirmed_at',
        ]);

        return $this->success(
            $this->formatRequest(
                $connectionRequest,
                (int) $request->user()->id,
                $this->visibility->hasConfirmedConnectionBetween(
                    (int) $connectionRequest->sender_id,
                    (int) $connectionRequest->receiver_id
                )
            ),
            'Request accepted.'
        );
    }

    public function reject(Request $request, ConnectionRequest $connectionRequest): JsonResponse
    {
        if ((int) $connectionRequest->receiver_id !== (int) $request->user()->id) {
            return $this->error('You are not allowed to reject this request.', 403);
        }

        if ($connectionRequest->status !== ConnectionRequestStatus::Pending) {
            return $this->error('Only pending requests can be rejected.');
        }

        $connectionRequest->update([
            'status' => ConnectionRequestStatus::Rejected->value,
            'responded_at' => Carbon::now(),
        ]);

        $connectionRequest->load([
            'sender.profile',
            'receiver.profile',
            'payments:id,connection_request_id,payer_id,status,amount,method,reference,confirmed_at',
        ]);

        return $this->success(
            $this->formatRequest(
                $connectionRequest,
                (int) $request->user()->id,
                $this->visibility->hasConfirmedConnectionBetween(
                    (int) $connectionRequest->sender_id,
                    (int) $connectionRequest->receiver_id
                )
            ),
            'Request rejected.'
        );
    }

    public function cancel(Request $request, ConnectionRequest $connectionRequest): JsonResponse
    {
        if ((int) $connectionRequest->sender_id !== (int) $request->user()->id) {
            return $this->error('You are not allowed to cancel this request.', 403);
        }

        if ($connectionRequest->status !== ConnectionRequestStatus::Pending) {
            return $this->error('Only pending requests can be cancelled.');
        }

        $connectionRequest->update([
            'status' => ConnectionRequestStatus::Cancelled->value,
            'responded_at' => Carbon::now(),
        ]);

        $connectionRequest->load([
            'sender.profile',
            'receiver.profile',
            'payments:id,connection_request_id,payer_id,status,amount,method,reference,confirmed_at',
        ]);

        return $this->success(
            $this->formatRequest($connectionRequest, (int) $request->user()->id, false),
            'Request cancelled.'
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, ConnectionRequest>  $items
     * @return array<int, bool>
     */
    private function buildCounterpartUnlockMap(Request $request, $items): array
    {
        $viewerId = (int) $request->user()->id;
        $counterpartIds = $items
            ->map(fn (ConnectionRequest $item): int => $this->counterpartUserId($item, $viewerId))
            ->unique()
            ->values()
            ->all();

        return array_fill_keys(
            $this->visibility->connectedUserIds($request->user(), $counterpartIds),
            true
        );
    }

    private function counterpartUserId(ConnectionRequest $request, int $viewerId): int
    {
        return (int) ($request->sender_id === $viewerId ? $request->receiver_id : $request->sender_id);
    }

    private function formatRequest(ConnectionRequest $request, int $viewerId, bool $isSensitiveUnlocked): array
    {
        $status = $request->status?->value ?? (string) $request->status;
        $isSender = (int) $request->sender_id === $viewerId;
        $isReceiver = (int) $request->receiver_id === $viewerId;
        $isActive = in_array($status, ConnectionRequestStatus::activeValues(), true);

        $senderName = $request->sender?->full_name;
        $receiverName = $request->receiver?->full_name;
        $senderProfileName = $request->sender?->profile?->display_name;
        $receiverProfileName = $request->receiver?->profile?->display_name;

        if ((int) $request->sender_id !== $viewerId && ! $isSensitiveUnlocked) {
            $senderName = self::LOCKED_NAME;
            $senderProfileName = self::LOCKED_NAME;
        }

        if ((int) $request->receiver_id !== $viewerId && ! $isSensitiveUnlocked) {
            $receiverName = self::LOCKED_NAME;
            $receiverProfileName = self::LOCKED_NAME;
        }

        $paymentSummary = $this->paymentFlow->paymentSummary($request, $viewerId);
        $currentUserPayment = $paymentSummary['current_user'];

        return [
            'id' => $request->id,
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'status' => $status,
            'message' => $request->message,
            'responded_at' => $request->responded_at,
            'connected_at' => $request->connected_at,
            'is_sender' => $isSender,
            'is_receiver' => $isReceiver,
            'is_active' => $isActive,
            'can_cancel' => $isSender && $status === ConnectionRequestStatus::Pending->value,
            'can_pay' => (bool) $currentUserPayment['can_submit'],
            'sender' => $request->relationLoaded('sender') ? [
                'id' => $request->sender?->id,
                'name' => $senderName,
                'profile_display_name' => $senderProfileName,
            ] : null,
            'receiver' => $request->relationLoaded('receiver') ? [
                'id' => $request->receiver?->id,
                'name' => $receiverName,
                'profile_display_name' => $receiverProfileName,
            ] : null,
            'payment' => $currentUserPayment['payment_id'] ? [
                'id' => $currentUserPayment['payment_id'],
                'status' => $currentUserPayment['status'],
                'amount' => $paymentSummary['connection_fee_amount'],
                'method' => $currentUserPayment['method'],
                'reference' => $currentUserPayment['reference'],
                'confirmed_at' => $currentUserPayment['confirmed_at'],
            ] : null,
            'payment_status' => $currentUserPayment['status'],
            'payment_summary' => $paymentSummary,
            'is_sensitive_unlocked' => $isSensitiveUnlocked,
            'created_at' => $request->created_at,
            'updated_at' => $request->updated_at,
        ];
    }
}
