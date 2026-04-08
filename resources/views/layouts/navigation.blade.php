<nav x-data="{ open: false }" class="border-b border-slate-200/70 bg-white/90 backdrop-blur">
    <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-6">
            <a href="{{ route('dashboard') }}" class="text-lg font-bold tracking-tight text-emerald-700">MatchConnect</a>

            <div class="hidden items-center gap-5 text-sm sm:flex">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-nav-link>
                <x-nav-link :href="route('browse.index')" :active="request()->routeIs('browse.*')">Browse</x-nav-link>
                <x-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">My Profile</x-nav-link>
                <x-nav-link :href="route('preferences.edit')" :active="request()->routeIs('preferences.*')">Preferences</x-nav-link>
                <x-nav-link :href="route('requests.sent')" :active="request()->routeIs('requests.sent')">Sent</x-nav-link>
                <x-nav-link :href="route('requests.received')" :active="request()->routeIs('requests.received')">Received</x-nav-link>
                <x-nav-link :href="route('payments.index')" :active="request()->routeIs('payments.*')">Payments</x-nav-link>
                @if(auth()->user()?->is_admin)
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">Admin</x-nav-link>
                @endif
            </div>
        </div>

        <div class="hidden sm:flex sm:items-center">
            <x-dropdown align="right" width="56">
                <x-slot name="trigger">
                    <button class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800">
                        <span>{{ auth()->user()->full_name }}</span>
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <div class="px-4 py-2 text-xs text-slate-500">{{ auth()->user()->phone }}</div>
                    <x-dropdown-link :href="route('profile.edit')">Profile</x-dropdown-link>
                    @if(auth()->user()?->is_admin)
                        <x-dropdown-link :href="route('admin.dashboard')">Admin Dashboard</x-dropdown-link>
                    @endif

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                            Log Out
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

        <button @click="open = ! open" class="sm:hidden rounded-md p-2 text-slate-600">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-slate-200 sm:hidden">
        <div class="space-y-1 px-4 py-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">Dashboard</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('browse.index')" :active="request()->routeIs('browse.*')">Browse</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('profile.edit')" :active="request()->routeIs('profile.*')">My Profile</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('preferences.edit')" :active="request()->routeIs('preferences.*')">Preferences</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('requests.sent')" :active="request()->routeIs('requests.sent')">Sent Requests</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('requests.received')" :active="request()->routeIs('requests.received')">Received Requests</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('payments.index')" :active="request()->routeIs('payments.*')">Payments</x-responsive-nav-link>
            @if(auth()->user()?->is_admin)
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">Admin</x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-slate-200 px-4 py-3">
            <div class="text-sm font-semibold text-slate-900">{{ auth()->user()->full_name }}</div>
            <div class="text-xs text-slate-500">{{ auth()->user()->phone }}</div>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                    Log Out
                </x-responsive-nav-link>
            </form>
        </div>
    </div>
</nav>
