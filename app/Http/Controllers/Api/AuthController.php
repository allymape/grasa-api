<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends ApiController
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'] ?? null,
            'phone' => $validated['phone'],
            'email' => $validated['email'] ?? null,
            'gender' => $validated['gender'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
            'is_blocked' => false,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->formatUser($user),
        ], 'Registration successful.', 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $login = trim($validated['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::where($field, $login)->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return $this->error('Invalid credentials.');
        }

        if (! $user->is_admin) {
            if ($user->is_blocked) {
                return $this->error('Your account is blocked. Please contact support.', 403);
            }

            if (! $user->is_active) {
                return $this->error('Your account is inactive. Please contact support.', 403);
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->formatUser($user),
        ], 'Login successful.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success([
            'user' => $this->formatUser($request->user()),
        ], 'Authenticated user retrieved.');
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'email' => $user->email,
            'gender' => $user->gender?->value ?? $user->gender,
            'is_admin' => (bool) $user->is_admin,
            'is_active' => (bool) $user->is_active,
            'is_blocked' => (bool) $user->is_blocked,
            'blocked_at' => $user->blocked_at,
            'blocked_reason' => $user->blocked_reason,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
