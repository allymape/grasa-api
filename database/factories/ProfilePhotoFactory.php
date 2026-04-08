<?php

namespace Database\Factories;

use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProfilePhoto>
 */
class ProfilePhotoFactory extends Factory
{
    protected $model = ProfilePhoto::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $variant = fake()->randomElement([
            'female/female-a.svg',
            'female/female-b.svg',
            'female/female-c.svg',
            'male/male-a.svg',
            'male/male-b.svg',
            'male/male-c.svg',
        ]);

        return [
            'user_id' => User::factory(),
            'profile_id' => Profile::factory(),
            'path' => "demo/profile-photos/{$variant}",
            'is_primary' => false,
            'sort_order' => 1,
        ];
    }
}
