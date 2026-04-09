<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));

        $users = User::query()
            ->withTrashed()
            ->with([
                'profile:id,user_id,display_name,age,date_of_birth,approval_status,is_visible,is_profile_complete',
            ])
            ->withCount([
                'sentConnectionRequests as sent_requests_count',
                'receivedConnectionRequests as received_requests_count',
                'payments',
                'reportsReceived as reports_count',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($builder) use ($search): void {
                    $builder
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(min(max((int) $request->integer('per_page', 20), 1), 100));

        return $this->success([
            'items' => $users->getCollection()->map(fn (User $user): array => $this->formatUserSummary($user))->all(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ], 'Users retrieved.');
    }

    public function show(int $user): JsonResponse
    {
        $model = $this->findUser($user);
        if (! $model) {
            return $this->error('User not found.', 404);
        }

        $model->load([
            'profile',
            'partnerPreference',
            'sentConnectionRequests' => fn ($query) => $query->latest('id')->limit(5),
            'receivedConnectionRequests' => fn ($query) => $query->latest('id')->limit(5),
            'payments' => fn ($query) => $query->latest('id')->limit(5),
        ])->loadCount([
            'sentConnectionRequests as sent_requests_count',
            'receivedConnectionRequests as received_requests_count',
            'payments',
            'reportsReceived as reports_count',
        ]);

        return $this->success($this->formatUserDetail($model), 'User details retrieved.');
    }

    public function block(Request $request, int $user): JsonResponse
    {
        $model = $this->findUser($user);
        if (! $model) {
            return $this->error('User not found.', 404);
        }

        if ((int) $request->user()->id === (int) $model->id) {
            return $this->error('You cannot block your own account.', 422);
        }

        $model->update([
            'is_blocked' => true,
            'blocked_at' => now(),
            'blocked_reason' => trim((string) $request->input('reason', 'Blocked by admin')) ?: 'Blocked by admin',
        ]);

        $model->load('profile');

        return $this->success($this->formatUserSummary($model), 'User blocked.');
    }

    public function unblock(int $user): JsonResponse
    {
        $model = $this->findUser($user);
        if (! $model) {
            return $this->error('User not found.', 404);
        }

        $model->update([
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);

        $model->load('profile');

        return $this->success($this->formatUserSummary($model), 'User unblocked.');
    }

    public function activate(int $user): JsonResponse
    {
        $model = $this->findUser($user);
        if (! $model) {
            return $this->error('User not found.', 404);
        }

        $model->update(['is_active' => true]);
        $model->load('profile');

        return $this->success($this->formatUserSummary($model), 'User activated.');
    }

    public function deactivate(Request $request, int $user): JsonResponse
    {
        $model = $this->findUser($user);
        if (! $model) {
            return $this->error('User not found.', 404);
        }

        if ((int) $request->user()->id === (int) $model->id) {
            return $this->error('You cannot deactivate your own account.', 422);
        }

        $model->update(['is_active' => false]);
        $model->load('profile');

        return $this->success($this->formatUserSummary($model), 'User deactivated.');
    }

    public function hideProfile(int $user): JsonResponse
    {
        $model = $this->findUser($user);
        if (! $model) {
            return $this->error('User not found.', 404);
        }

        if (! $model->profile) {
            return $this->error('Profile not found for this user.', 404);
        }

        $model->profile->update(['is_visible' => false]);
        $model->load('profile');

        return $this->success($this->formatUserSummary($model), 'Profile hidden from browse results.');
    }

    public function unhideProfile(int $user): JsonResponse
    {
        $model = $this->findUser($user);
        if (! $model) {
            return $this->error('User not found.', 404);
        }

        if (! $model->profile) {
            return $this->error('Profile not found for this user.', 404);
        }

        $model->profile->update(['is_visible' => true]);
        $model->load('profile');

        return $this->success($this->formatUserSummary($model), 'Profile is now visible in browse results.');
    }

    public function destroy(Request $request, int $user): JsonResponse
    {
        $model = $this->findUser($user);
        if (! $model) {
            return $this->error('User not found.', 404);
        }

        if ((int) $request->user()->id === (int) $model->id) {
            return $this->error('You cannot delete your own account.', 422);
        }

        if ($model->trashed()) {
            return $this->success($this->formatUserSummary($model), 'User already deleted.');
        }

        if ($model->profile) {
            $model->profile->update(['is_visible' => false]);
        }

        $model->delete();
        $model->refresh();
        $model->load('profile');

        return $this->success($this->formatUserSummary($model), 'User deleted.');
    }

    private function findUser(int $userId): ?User
    {
        return User::query()
            ->withTrashed()
            ->with('profile:id,user_id,display_name,age,date_of_birth,approval_status,is_visible,is_profile_complete')
            ->find($userId);
    }

    private function formatUserSummary(User $user): array
    {
        $sentRequestsCount = (int) ($user->sent_requests_count ?? 0);
        $receivedRequestsCount = (int) ($user->received_requests_count ?? 0);
        $requestsCount = $sentRequestsCount + $receivedRequestsCount;

        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'gender' => $user->gender?->value ?? $user->gender,
            'is_admin' => (bool) $user->is_admin,
            'is_active' => (bool) $user->is_active,
            'is_blocked' => (bool) $user->is_blocked,
            'blocked_at' => $user->blocked_at,
            'blocked_reason' => $user->blocked_reason,
            'deleted_at' => $user->deleted_at,
            'profile' => $user->profile ? [
                'id' => $user->profile->id,
                'display_name' => $user->profile->display_name,
                'age' => (int) $user->profile->age,
                'date_of_birth' => $user->profile->date_of_birth?->toDateString(),
                'approval_status' => $user->profile->approval_status?->value ?? $user->profile->approval_status,
                'is_visible' => (bool) $user->profile->is_visible,
                'is_profile_complete' => (bool) $user->profile->is_profile_complete,
            ] : null,
            'summary' => [
                'profile_approval_status' => $user->profile?->approval_status?->value ?? $user->profile?->approval_status,
                'visibility_status' => $user->profile ? (bool) $user->profile->is_visible : null,
                'requests_count' => $requestsCount,
                'payments_count' => (int) ($user->payments_count ?? 0),
                'reports_count' => (int) ($user->reports_count ?? 0),
            ],
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function formatUserDetail(User $user): array
    {
        return [
            ...$this->formatUserSummary($user),
            'partner_preference' => $user->partnerPreference,
            'recent_activity' => [
                'sent_requests' => $user->sentConnectionRequests->map(fn ($item) => [
                    'id' => $item->id,
                    'receiver_id' => $item->receiver_id,
                    'status' => $item->status?->value ?? $item->status,
                    'created_at' => $item->created_at,
                ])->all(),
                'received_requests' => $user->receivedConnectionRequests->map(fn ($item) => [
                    'id' => $item->id,
                    'sender_id' => $item->sender_id,
                    'status' => $item->status?->value ?? $item->status,
                    'created_at' => $item->created_at,
                ])->all(),
                'payments' => $user->payments->map(fn ($item) => [
                    'id' => $item->id,
                    'connection_request_id' => $item->connection_request_id,
                    'status' => $item->status?->value ?? $item->status,
                    'amount' => (string) $item->amount,
                    'created_at' => $item->created_at,
                ])->all(),
            ],
        ];
    }
}
