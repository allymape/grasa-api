<x-guest-layout>
    <h2 class="text-xl font-bold text-slate-900">Create your matchmaking profile account</h2>
    <p class="mt-1 text-sm text-slate-600">Start with your basic details. You can complete your full profile after login.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-5 space-y-4">
        @csrf

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="first_name" :value="__('First Name')" />
                <x-text-input id="first_name" class="mt-1 block w-full" type="text" name="first_name" :value="old('first_name')" required autofocus autocomplete="given-name" />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="last_name" :value="__('Last Name (Optional)')" />
                <x-text-input id="last_name" class="mt-1 block w-full" type="text" name="last_name" :value="old('last_name')" autocomplete="family-name" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone')" />
            <x-text-input id="phone" class="mt-1 block w-full" type="text" name="phone" :value="old('phone')" required autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email (Optional)')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="gender" :value="__('Gender')" />
            <select id="gender" name="gender" class="mt-1 block w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500" required>
                <option value="">Select gender</option>
                <option value="male" @selected(old('gender') === 'male')>Male</option>
                <option value="female" @selected(old('gender') === 'female')>Female</option>
            </select>
            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="password" :value="__('Password')" />
                <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>

        <div class="flex items-center justify-between pt-2">
            <a class="text-sm text-emerald-700 hover:text-emerald-600" href="{{ route('login') }}">
                Already registered?
            </a>

            <x-primary-button>
                Register
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
