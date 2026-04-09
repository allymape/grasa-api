<?php

namespace App\Http\Controllers;

use App\Enums\ConnectionRequestStatus;
use App\Enums\ProfileApprovalStatus;
use App\Http\Requests\StoreReportRequest;
use App\Models\ConnectionRequest;
use App\Models\Profile;
use App\Models\Report;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class BrowseProfileController extends Controller
{
    public function index(Request $request): View
    {
        $profilesQuery = Profile::query()
            ->with([
                'user',
                'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order'),
            ])
            ->where('approval_status', ProfileApprovalStatus::Approved->value)
            ->where('is_visible', true)
            ->where('user_id', '!=', Auth::id())
            ->when($request->filled('gender'), function ($query) use ($request) {
                $query->whereHas('user', fn ($userQuery) => $userQuery->where('gender', (string) $request->input('gender')));
            })
            ->when($request->filled('region'), fn ($query) => $query->where('region', (string) $request->input('region')))
            ->when($request->filled('religion'), fn ($query) => $query->where('religion', (string) $request->input('religion')))
            ->when($request->filled('marital_status'), fn ($query) => $query->where('marital_status', (string) $request->input('marital_status')))
            ->when($request->filled('has_children'), fn ($query) => $query->where('has_children', $request->boolean('has_children')));

        $this->applyAgeFilters(
            $profilesQuery,
            $request->filled('min_age') ? (int) $request->integer('min_age') : null,
            $request->filled('max_age') ? (int) $request->integer('max_age') : null
        );

        $profiles = $profilesQuery
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('browse.index', [
            'profiles' => $profiles,
            'filters' => $request->only(['gender', 'min_age', 'max_age', 'region', 'religion', 'marital_status', 'has_children']),
        ]);
    }

    public function show(Profile $profile): View
    {
        $profile->load(['user', 'photos' => fn ($query) => $query->orderByDesc('is_primary')->orderBy('sort_order')]);

        $this->authorize('view', $profile);

        $connectedRequest = ConnectionRequest::query()
            ->where('status', ConnectionRequestStatus::Connected->value)
            ->where(function ($query) use ($profile) {
                $query
                    ->where(function ($forward) use ($profile) {
                        $forward->where('sender_id', Auth::id())->where('receiver_id', $profile->user_id);
                    })
                    ->orWhere(function ($reverse) use ($profile) {
                        $reverse->where('sender_id', $profile->user_id)->where('receiver_id', Auth::id());
                    });
            })
            ->first();

        $canViewContact = (bool) $connectedRequest;

        return view('browse.show', compact('profile', 'canViewContact', 'connectedRequest'));
    }

    public function report(StoreReportRequest $request, Profile $profile)
    {
        if ($profile->user_id === $request->user()->id) {
            return Redirect::back()->withErrors(['reason' => 'You cannot report your own profile.']);
        }

        Report::create([
            'reporter_id' => $request->user()->id,
            'reported_user_id' => $profile->user_id,
            'reason' => (string) $request->input('reason'),
            'details' => $request->filled('details') ? (string) $request->input('details') : null,
        ]);

        return Redirect::back()->with('status', 'report-submitted');
    }

    private function applyAgeFilters(Builder $query, ?int $minAge, ?int $maxAge): void
    {
        if ($minAge === null && $maxAge === null) {
            return;
        }

        $driver = $query->getConnection()->getDriverName();
        $ageExpression = match ($driver) {
            'mysql' => 'COALESCE(TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()), age)',
            'pgsql' => 'COALESCE(EXTRACT(YEAR FROM age(CURRENT_DATE, date_of_birth))::int, age)',
            'sqlite' => "COALESCE((CAST(strftime('%Y', 'now') AS INTEGER) - CAST(strftime('%Y', date_of_birth) AS INTEGER) - (strftime('%m-%d', 'now') < strftime('%m-%d', date_of_birth))), age)",
            default => 'age',
        };

        if ($minAge !== null) {
            $query->whereRaw("{$ageExpression} >= ?", [$minAge]);
        }

        if ($maxAge !== null) {
            $query->whereRaw("{$ageExpression} <= ?", [$maxAge]);
        }
    }
}
