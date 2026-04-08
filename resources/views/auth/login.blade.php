<x-guest-layout>
    <h2 class="text-xl font-bold text-slate-900">Login to your account</h2>
    <p class="mt-1 text-sm text-slate-600">Use your phone number or email with your password.</p>

    <x-auth-session-status class="mb-4 mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-4">
        @csrf

        <div>
            <x-input-label for="login" :value="__('Phone or Email')" />
            <x-text-input id="login" class="mt-1 block w-full" type="text" name="login" :value="old('login')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('login')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label class="inline-flex items-center gap-2">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-emerald-600 shadow-sm focus:ring-emerald-500" name="remember">
            <span class="text-sm text-slate-600">Remember me</span>
        </label>

        <div class="flex items-center justify-between pt-2">
            @if (Route::has('password.request'))
                <a class="text-sm text-emerald-700 hover:text-emerald-600" href="{{ route('password.request') }}">
                    Forgot password?
                </a>
            @endif

            <x-primary-button>
                Log in
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
