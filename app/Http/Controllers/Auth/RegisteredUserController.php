<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Gender;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30', Rule::unique(User::class, 'phone')],
            'email' => ['nullable', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'gender' => ['required', Rule::in(array_column(Gender::cases(), 'value'))],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $firstName = trim((string) $request->input('first_name'));
        $lastName = trim((string) $request->input('last_name', ''));
        $phone = trim((string) $request->input('phone'));
        $email = trim((string) $request->input('email', ''));
        $gender = trim((string) $request->input('gender'));

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName !== '' ? $lastName : null,
            'phone' => $phone,
            'email' => $email !== '' ? $email : null,
            'gender' => $gender,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
