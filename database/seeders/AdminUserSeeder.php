<?php

namespace Database\Seeders;

use App\Enums\Gender;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'admin12345';

        $admin = User::query()->firstOrNew(['phone' => '0711000000']);
        $admin->fill([
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin@matchmaking.test',
            'gender' => Gender::Male->value,
            'is_admin' => true,
            'is_active' => true,
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
            'email_verified_at' => now(),
        ]);

        if (! $admin->exists || ! Hash::check($password, (string) $admin->password)) {
            $admin->password = Hash::make($password);
        }

        $admin->save();
    }
}
