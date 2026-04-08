<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-slate-900">Welcome, {{ $user->first_name }}</h2>
            <a href="{{ route('browse.index') }}" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white">Browse Matches</a>
        </div>
    </x-slot>

    <div class="mx-auto mt-8 max-w-7xl px-4 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('status') }}
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Available Profiles</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ $matchesCount }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Sent Requests</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ $sentCount }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Received Pending</p>
                <p class="mt-2 text-2xl font-black text-slate-900">{{ $receivedPendingCount }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Profile Status</p>
                <p class="mt-2 text-sm font-semibold text-slate-900">{{ $user->profile?->approval_status?->value ?? 'not_created' }}</p>
            </div>
        </div>

        <div class="mt-6 grid gap-5 lg:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-bold text-slate-900">Profile Checklist</h3>
                <ul class="mt-3 space-y-2 text-sm text-slate-700">
                    <li>Profile: {{ $user->profile ? 'Completed' : 'Not yet completed' }}</li>
                    <li>Preferences: {{ $user->partnerPreference ? 'Configured' : 'Not yet configured' }}</li>
                    <li>Visibility: {{ $user->profile?->is_visible ? 'Visible' : 'Hidden' }}</li>
                </ul>

                <div class="mt-4 flex flex-wrap gap-2">
                    <a href="{{ route('profile.edit') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold">Edit Profile</a>
                    <a href="{{ route('preferences.edit') }}" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold">Set Preferences</a>
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-base font-bold text-slate-900">Next Steps</h3>
                <ol class="mt-3 list-inside list-decimal space-y-2 text-sm text-slate-700">
                    <li>Complete your profile with quality photos.</li>
                    <li>Set partner preferences to improve matching.</li>
                    <li>Browse approved profiles and send requests.</li>
                    <li>Track request/payment status from your dashboard.</li>
                </ol>
            </div>
        </div>
    </div>
</x-app-layout>
