<?php

namespace Database\Factories;

use App\Enums\BodyType;
use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Enums\ProfileApprovalStatus;
use App\Enums\Religion;
use App\Enums\SkinTone;
use App\Models\Country;
use App\Models\District;
use App\Models\Profile;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Profile>
 */
class ProfileFactory extends Factory
{
    protected $model = Profile::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $country = Country::query()->inRandomOrder()->first();
        $regionId = null;
        $districtId = null;

        if ($country && $country->requires_region_district) {
            $region = Region::query()
                ->where('country_id', $country->id)
                ->inRandomOrder()
                ->first();

            $regionId = $region?->id;
            $districtId = $region
                ? District::query()
                    ->where('region_id', $region->id)
                    ->inRandomOrder()
                    ->value('id')
                : null;
        }

        $employment = fake()->randomElement(array_column(EmploymentStatus::cases(), 'value'));
        $hasChildren = fake()->boolean(38);
        $dateOfBirth = Carbon::instance(fake()->dateTimeBetween('-48 years', '-18 years'));
        $age = $dateOfBirth->age;

        return [
            'user_id' => User::factory(),
            'display_name' => fake()->firstName().' '.fake()->lastName(),
            'age' => $age,
            'date_of_birth' => $dateOfBirth->toDateString(),
            'country_id' => $country?->id ?? Country::query()->value('id'),
            'region_id' => $regionId,
            'district_id' => $districtId,
            'current_residence' => fake()->randomElement([
                'City Centre',
                'Mikocheni',
                'Mlimani',
                'Ubungo',
                'Masaki',
                'Kariakoo',
                'Njiro',
                'Kilimani',
                'Ntinda',
                'Kimironko',
            ]),
            'height_cm' => fake()->numberBetween(148, 196),
            'employment_status' => $employment,
            'job_title' => $this->jobTitleForEmployment($employment),
            'marital_status' => fake()->randomElement(array_column(MaritalStatus::cases(), 'value')),
            'has_children' => $hasChildren,
            'children_count' => $hasChildren ? fake()->numberBetween(1, 4) : 0,
            'religion' => fake()->randomElement(array_column(Religion::cases(), 'value')),
            'body_type' => fake()->optional(0.9)->randomElement(array_column(BodyType::cases(), 'value')),
            'skin_tone' => fake()->optional(0.9)->randomElement(array_column(SkinTone::cases(), 'value')),
            'about_me' => fake()->randomElement($this->aboutMeSamples()),
            'life_outlook' => fake()->randomElement($this->lifeOutlookSamples()),
            'approval_status' => ProfileApprovalStatus::Approved->value,
            'is_profile_complete' => true,
            'is_visible' => true,
            'approved_by' => null,
            'approved_at' => now(),
        ];
    }

    /**
     * @return list<string>
     */
    private function aboutMeSamples(): array
    {
        return [
            'Grounded and optimistic, I value consistency, kindness, and honest communication.',
            'I enjoy simple routines, meaningful conversations, and building a peaceful life with purpose.',
            'Family matters to me, and I appreciate a partner who is calm, respectful, and emotionally mature.',
            'I am social when needed but happiest with genuine people and intentional plans.',
            'I am practical, loyal, and ready for a committed relationship rooted in trust.',
        ];
    }

    /**
     * @return list<string>
     */
    private function lifeOutlookSamples(): array
    {
        return [
            'I believe a strong relationship is built through patience, growth, and showing up for each other daily.',
            'My goal is a stable marriage where both people feel seen, safe, and supported.',
            'I value progress over perfection and want to build a future with shared values and teamwork.',
            'I see partnership as friendship, compassion, and commitment through different life seasons.',
            'I want a home atmosphere that is warm, respectful, and focused on long-term goals.',
        ];
    }

    private function jobTitleForEmployment(string $employment): ?string
    {
        return match ($employment) {
            EmploymentStatus::Employed->value => fake()->randomElement([
                'Teacher',
                'Nurse',
                'Accountant',
                'Software Developer',
                'Project Coordinator',
                'Sales Executive',
                'Logistics Officer',
                'Bank Teller',
            ]),
            EmploymentStatus::SelfEmployed->value => fake()->randomElement([
                'Business Owner',
                'Tailor',
                'Photographer',
                'Salon Owner',
                'Digital Marketer',
                'Consultant',
            ]),
            EmploymentStatus::Student->value => fake()->randomElement([
                'University Student',
                'Medical Student',
                'Law Student',
                'Engineering Student',
            ]),
            EmploymentStatus::Unemployed->value => null,
            default => fake()->randomElement([
                'Freelancer',
                'Community Volunteer',
                'Part-time Consultant',
            ]),
        };
    }
}
