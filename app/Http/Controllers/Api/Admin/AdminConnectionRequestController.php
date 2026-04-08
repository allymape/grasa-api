<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\ConnectionRequest;
use App\Services\ConnectionPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminConnectionRequestController extends ApiController
{
    public function __construct(
        private readonly ConnectionPaymentService $paymentFlow
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $requests = ConnectionRequest::query()
            ->with([
                'sender:id,first_name,last_name,phone,email,gender',
                'receiver:id,first_name,last_name,phone,email,gender',
                'sender.profile:id,user_id,display_name',
                'receiver.profile:id,user_id,display_name',
                'payments:id,connection_request_id,payer_id,status,amount,method,reference,confirmed_at',
            ])
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->input('status')))
            ->latest('id')
            ->paginate(min(max((int) $request->integer('per_page', 15), 1), 100));

        return $this->success([
            'items' => $requests->getCollection()->map(fn (ConnectionRequest $item): array => $this->formatRequest($item))->all(),
            'meta' => [
                'current_page' => $requests->currentPage(),
                'last_page' => $requests->lastPage(),
                'per_page' => $requests->perPage(),
                'total' => $requests->total(),
            ],
        ], 'Connection requests retrieved.');
    }

    private function formatRequest(ConnectionRequest $request): array
    {
        $paymentSummaryForSender = $this->paymentFlow->paymentSummary($request, (int) $request->sender_id);

        return [
            'id' => $request->id,
            'sender_id' => $request->sender_id,
            'receiver_id' => $request->receiver_id,
            'status' => $request->status?->value ?? $request->status,
            'message' => $request->message,
            'responded_at' => $request->responded_at,
            'connected_at' => $request->connected_at,
            'sender' => $request->relationLoaded('sender') && $request->sender ? [
                'id' => $request->sender->id,
                'first_name' => $request->sender->first_name,
                'last_name' => $request->sender->last_name,
                'phone' => $request->sender->phone,
                'email' => $request->sender->email,
                'gender' => $request->sender->gender?->value ?? $request->sender->gender,
                'profile_display_name' => $request->sender->relationLoaded('profile') ? $request->sender->profile?->display_name : null,
            ] : null,
            'receiver' => $request->relationLoaded('receiver') && $request->receiver ? [
                'id' => $request->receiver->id,
                'first_name' => $request->receiver->first_name,
                'last_name' => $request->receiver->last_name,
                'phone' => $request->receiver->phone,
                'email' => $request->receiver->email,
                'gender' => $request->receiver->gender?->value ?? $request->receiver->gender,
                'profile_display_name' => $request->receiver->relationLoaded('profile') ? $request->receiver->profile?->display_name : null,
            ] : null,
            'payments' => $request->relationLoaded('payments')
                ? $request->payments->map(fn ($payment): array => [
                    'id' => $payment->id,
                    'payer_id' => $payment->payer_id,
                    'status' => $payment->status?->value ?? $payment->status,
                    'amount' => (string) $payment->amount,
                    'method' => $payment->method?->value ?? $payment->method,
                    'reference' => $payment->reference,
                    'confirmed_at' => $payment->confirmed_at,
                ])->all()
                : [],
            'payment_summary' => [
                'connection_fee_amount' => $paymentSummaryForSender['connection_fee_amount'],
                'sender' => $paymentSummaryForSender['current_user'],
                'receiver' => $paymentSummaryForSender['counterpart'],
                'both_confirmed' => $paymentSummaryForSender['both_confirmed'],
            ],
            'created_at' => $request->created_at,
            'updated_at' => $request->updated_at,
        ];
    }
}
