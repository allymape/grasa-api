<?php

namespace App\Http\Controllers\Api;

use App\Enums\ConnectionRequestStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\ProfileApprovalStatus;
use App\Enums\Religion;
use App\Models\ConnectionRequest;
use App\Models\Profile;
use App\Services\ConnectionPaymentService;
use App\Services\ProfileVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BrowseProfileController extends ApiController
{
    private const LOCKED_NAME = 'Private Member';
    private const LOCK_MESSAGE = 'Photos, name, and contact details unlock after both matched users complete and confirm payment.';
    private const TEXT_PLACEHOLDER = 'Not provided yet';

    public function __construct(
        private readonly ProfileVisibilityService $visibility,
        private readonly ConnectionPaymentService $paymentFlow
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'gender' => ['nullable', Rule::in(array_column(Gender::cases(), 'value'))],
            'min_age' => ['nullable', 'integer', 'min:18', 'max:80'],
            'max_age' => ['nullable', 'integer', 'min:18', 'max:80', 'gte:min_age'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'country_iso2' => ['nullable', 'string', 'size:2'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'religion' => ['nullable', Rule::in(array_column(Religion::cases(), 'value'))],
            'marital_status' => ['nullable', Rule::in(array_column(MaritalStatus::cases(), 'value'))],
            'has_children' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $countryIso2 = isset($validated['country_iso2']) ? strtoupper((string) $validated['country_iso2']) : null;

        $profiles = Profile::query()
            ->with([
                'user:id,first_name,last_name,phone,email,gender',
                'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
                'country:id,name,iso2,phone_code,flag',
                'region:id,country_id,name,code',
                'district:id,region_id,name,code',
            ])
            ->where('approval_status', ProfileApprovalStatus::Approved->value)
            ->where('is_visible', true)
            ->where('user_id', '!=', $request->user()->id)
            ->when(isset($validated['gender']), function ($query) use ($validated) {
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('gender', (string) $validated['gender']));
            })
            ->when(isset($validated['min_age']), fn ($query) => $query->where('age', '>=', (int) $validated['min_age']))
            ->when(isset($validated['max_age']), fn ($query) => $query->where('age', '<=', (int) $validated['max_age']))
            ->when(isset($validated['country_id']), fn ($query) => $query->where('country_id', (int) $validated['country_id']))
            ->when($countryIso2, fn ($query) => $query->whereHas('country', fn ($countryQuery) => $countryQuery->where('iso2', $countryIso2)))
            ->when(isset($validated['region_id']), fn ($query) => $query->where('region_id', (int) $validated['region_id']))
            ->when(isset($validated['district_id']), fn ($query) => $query->where('district_id', (int) $validated['district_id']))
            ->when(isset($validated['religion']), fn ($query) => $query->where('religion', (string) $validated['religion']))
            ->when(isset($validated['marital_status']), fn ($query) => $query->where('marital_status', (string) $validated['marital_status']))
            ->when(array_key_exists('has_children', $validated), fn ($query) => $query->where('has_children', (bool) $validated['has_children']))
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 12))
            ->withQueryString();

        $profileItems = $profiles->getCollection();
        $candidateUserIds = $profileItems
            ->pluck('user_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $unlockedUserIdMap = array_fill_keys(
            $this->visibility->connectedUserIds($request->user(), $candidateUserIds),
            true
        );

        return $this->success([
            'items' => $profileItems
                ->map(fn (Profile $profile): array => $this->formatProfile(
                    $profile,
                    isset($unlockedUserIdMap[(int) $profile->user_id])
                ))
                ->all(),
            'meta' => [
                'current_page' => $profiles->currentPage(),
                'last_page' => $profiles->lastPage(),
                'per_page' => $profiles->perPage(),
                'total' => $profiles->total(),
            ],
        ], 'Profiles retrieved.');
    }

    public function show(Request $request, Profile $profile): JsonResponse
    {
        if (
            $profile->approval_status !== ProfileApprovalStatus::Approved
            || ! $profile->is_visible
            || $profile->user_id === $request->user()->id
        ) {
            return $this->error('Profile not found.', 404);
        }

        $profile->load([
            'user:id,first_name,last_name,phone,email,gender',
            'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
            'country:id,name,iso2,phone_code,flag',
            'region:id,country_id,name,code',
            'district:id,region_id,name,code',
        ]);

        $connectionRequest = $this->latestConnectionRequestBetween(
            (int) $request->user()->id,
            (int) $profile->user_id
        );
        $isSensitiveUnlocked = $this->visibility->canViewSensitive($request->user(), $profile);

        return $this->success(
            $this->formatProfile(
                $profile,
                $isSensitiveUnlocked,
                $this->buildConnectionContext((int) $request->user()->id, $connectionRequest)
            ),
            'Profile retrieved.'
        );
    }

    private function formatProfile(Profile $profile, bool $isSensitiveUnlocked, ?array $connection = null): array
    {
        $primaryPhoto = $isSensitiveUnlocked
            ? ($profile->photos->firstWhere('is_primary', true) ?? $profile->photos->first())
            : null;
        $displayName = $isSensitiveUnlocked ? $profile->display_name : self::LOCKED_NAME;
        $religion = $profile->religion?->value ?? $profile->religion ?? self::TEXT_PLACEHOLDER;
        $maritalStatus = $profile->marital_status?->value ?? $profile->marital_status ?? self::TEXT_PLACEHOLDER;
        $country = $profile->country ? [
            'id' => $profile->country->id,
            'name' => $profile->country->name,
            'iso2' => $profile->country->iso2,
            'phone_code' => $profile->country->phone_code,
            'flag' => $profile->country->flag,
        ] : [
            'id' => 0,
            'name' => 'Location not specified',
            'iso2' => '--',
            'phone_code' => '',
            'flag' => '',
        ];
        $aboutMe = trim((string) $profile->about_me);
        $shortBio = $aboutMe === ''
            ? self::TEXT_PLACEHOLDER
            : Str::of($aboutMe)->limit(220)->toString();

        return [
            'id' => $profile->id,
            'display_name' => $displayName,
            'age' => (int) ($profile->age ?? 18),
            'country_id' => $profile->country_id,
            'region_id' => $profile->region_id,
            'district_id' => $profile->district_id,
            'country' => $country,
            'region' => $profile->region ? [
                'id' => $profile->region->id,
                'name' => $profile->region->name,
                'code' => $profile->region->code,
            ] : null,
            'district' => $profile->district ? [
                'id' => $profile->district->id,
                'name' => $profile->district->name,
                'code' => $profile->district->code,
            ] : null,
            'current_residence' => $isSensitiveUnlocked
                ? ($profile->current_residence ?: self::TEXT_PLACEHOLDER)
                : ($country['name'] ?: self::TEXT_PLACEHOLDER),
            'height_cm' => $isSensitiveUnlocked ? (int) $profile->height_cm : 0,
            'employment_status' => $isSensitiveUnlocked
                ? ($profile->employment_status?->value ?? $profile->employment_status ?? self::TEXT_PLACEHOLDER)
                : 'Hidden until both payments are confirmed.',
            'job_title' => $isSensitiveUnlocked ? $profile->job_title : null,
            'marital_status' => $maritalStatus,
            'has_children' => $isSensitiveUnlocked ? (bool) $profile->has_children : false,
            'children_count' => $isSensitiveUnlocked ? (int) $profile->children_count : 0,
            'religion' => $religion,
            'body_type' => $isSensitiveUnlocked ? ($profile->body_type?->value ?? $profile->body_type) : null,
            'skin_tone' => $isSensitiveUnlocked ? ($profile->skin_tone?->value ?? $profile->skin_tone) : null,
            'about_me' => $isSensitiveUnlocked ? $aboutMe : $shortBio,
            'life_outlook' => $isSensitiveUnlocked
                ? ($profile->life_outlook ?: self::TEXT_PLACEHOLDER)
                : 'Visible after both payments are confirmed.',
            'photos' => $isSensitiveUnlocked ? $profile->photos->map(fn ($photo): array => [
                'id' => $photo->id,
                'path' => "/api/profile-photos/{$photo->id}",
                'is_primary' => (bool) $photo->is_primary,
                'sort_order' => $photo->sort_order,
            ])->all() : [],
            'primary_photo' => $primaryPhoto ? [
                'id' => $primaryPhoto->id,
                'path' => "/api/profile-photos/{$primaryPhoto->id}",
            ] : null,
            'is_sensitive_unlocked' => $isSensitiveUnlocked,
            'sensitive_lock_message' => $isSensitiveUnlocked ? null : self::LOCK_MESSAGE,
            'user' => [
                'id' => $profile->user->id,
                'first_name' => $isSensitiveUnlocked ? $profile->user->first_name : 'Private',
                'last_name' => $isSensitiveUnlocked ? $profile->user->last_name : 'Member',
                'gender' => $profile->user->gender?->value ?? $profile->user->gender,
                'phone' => $isSensitiveUnlocked ? $profile->user->phone : null,
                'email' => $isSensitiveUnlocked ? $profile->user->email : null,
            ],
            'connection' => $connection,
        ];
    }

    private function latestConnectionRequestBetween(int $viewerId, int $targetUserId): ?ConnectionRequest
    {
        return ConnectionRequest::query()
            ->with('payments:id,connection_request_id,payer_id,status,amount,method,reference,confirmed_at')
            ->where(function ($query) use ($viewerId, $targetUserId): void {
                $query
                    ->where(function ($forward) use ($viewerId, $targetUserId): void {
                        $forward->where('sender_id', $viewerId)->where('receiver_id', $targetUserId);
                    })
                    ->orWhere(function ($reverse) use ($viewerId, $targetUserId): void {
                        $reverse->where('sender_id', $targetUserId)->where('receiver_id', $viewerId);
                    });
            })
            ->latest('id')
            ->first();
    }

    private function buildConnectionContext(int $viewerId, ?ConnectionRequest $connectionRequest): array
    {
        if (! $connectionRequest) {
            return [
                'can_send_request' => true,
                'resend_allowed' => true,
                'has_active_request' => false,
                'pricing' => [
                    'connection_fee_amount' => $this->paymentFlow->getConnectionFeeAmount(),
                ],
                'request' => null,
                'latest_request' => null,
            ];
        }

        $status = $connectionRequest->status?->value ?? (string) $connectionRequest->status;
        $isSender = (int) $connectionRequest->sender_id === $viewerId;
        $isReceiver = (int) $connectionRequest->receiver_id === $viewerId;
        $isActive = in_array($status, ConnectionRequestStatus::activeValues(), true);
        $paymentSummary = $this->paymentFlow->paymentSummary($connectionRequest, $viewerId);
        $currentUserPayment = $paymentSummary['current_user'];

        $summary = [
            'id' => $connectionRequest->id,
            'status' => $status,
            'message' => $connectionRequest->message,
            'is_sender' => $isSender,
            'is_receiver' => $isReceiver,
            'is_active' => $isActive,
            'can_cancel' => $isSender && $status === ConnectionRequestStatus::Pending->value,
            'can_pay' => (bool) $currentUserPayment['can_submit'],
            'payment_status' => $currentUserPayment['status'],
            'payment_summary' => $paymentSummary,
            'created_at' => $connectionRequest->created_at,
            'responded_at' => $connectionRequest->responded_at,
            'connected_at' => $connectionRequest->connected_at,
        ];

        return [
            'can_send_request' => ! $isActive,
            'resend_allowed' => ! $isActive,
            'has_active_request' => $isActive,
            'pricing' => [
                'connection_fee_amount' => $paymentSummary['connection_fee_amount'],
            ],
            'request' => $isActive ? $summary : null,
            'latest_request' => $isActive ? null : $summary,
        ];
    }
}
