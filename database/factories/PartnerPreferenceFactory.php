<?php

namespace Database\Factories;

use App\Enums\BodyType;
use App\Enums\Gender;
use App\Enums\Religion;
use App\Enums\SkinTone;
use App\Models\PartnerPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartnerPreference>
 */
class PartnerPreferenceFactory extends Factory
{
    protected $model = PartnerPreference::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $minAge = fake()->numberBetween(20, 34);
        $maxAge = min(60, $minAge + fake()->numberBetween(4, 16));

        return [
            'user_id' => User::factory(),
            'preferred_gender' => fake()->randomElement(array_column(Gender::cases(), 'value')),
            'min_age' => $minAge,
            'max_age' => $maxAge,
            'preferred_religion' => fake()->optional(0.55)->randomElement(array_column(Religion::cases(), 'value')),
            'must_have_job' => fake()->boolean(55),
            'must_be_calm' => fake()->boolean(75),
            'must_love_children' => fake()->boolean(55),
            'must_be_modest' => fake()->boolean(48),
            'must_be_respectful' => true,
            'preferred_skin_tone' => fake()->optional(0.4)->randomElement(array_column(SkinTone::cases(), 'value')),
            'preferred_body_type' => fake()->optional(0.4)->randomElement(array_column(BodyType::cases(), 'value')),
            'additional_notes' => fake()->optional(0.7)->randomElement([
                'Looking for clear communication and long-term commitment.',
                'Values emotional maturity, kindness, and consistency.',
                'Open to a partner from a different background if values align.',
                'Hoping to build a peaceful and supportive home together.',
                'Prefer someone intentional, family-oriented, and dependable.',
            ]),
        ];
    }
}
