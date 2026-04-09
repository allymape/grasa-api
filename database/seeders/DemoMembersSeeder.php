<?php

namespace Database\Seeders;

use App\Enums\BodyType;
use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\ProfileApprovalStatus;
use App\Enums\Religion;
use App\Enums\SkinTone;
use App\Models\Country;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\ProfilePhoto;
use App\Models\Region;
use App\Models\User;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DemoMembersSeeder extends Seeder
{
    private const DEMO_EMAIL_DOMAIN = 'demo.grasa.local';

    private const DEMO_PHONE_PREFIX = '079990';

    private const TOTAL_MEMBERS = 100;

    private const FEMALE_COUNT = 50;

    private const MALE_COUNT = 50;

    private Generator $faker;

    /**
     * @var list<string>
     */
    private array $maleFirstNames = [
        'Baraka', 'Juma', 'Amani', 'Emmanuel', 'Musa', 'Khamis', 'Hassan', 'Abdallah', 'Yonas', 'Brian',
        'Peter', 'Kelvin', 'Moses', 'Elijah', 'Patrick', 'Noah', 'Ibrahim', 'Samuel', 'Jacob', 'Daniel',
        'Dennis', 'Luka', 'Geofrey', 'Mark', 'Joseph', 'Kevin', 'Clifford', 'Edwin', 'Ronald', 'James',
    ];

    /**
     * @var list<string>
     */
    private array $femaleFirstNames = [
        'Asha', 'Neema', 'Rehema', 'Fatma', 'Jamila', 'Saada', 'Grace', 'Mercy', 'Lilian', 'Zawadi',
        'Mariam', 'Halima', 'Agnes', 'Brenda', 'Joy', 'Diana', 'Ruth', 'Deborah', 'Tabitha', 'Ester',
        'Nadia', 'Aisha', 'Martha', 'Farida', 'Gloria', 'Anna', 'Janeth', 'Upendo', 'Wema', 'Clara',
    ];

    /**
     * @var list<string>
     */
    private array $lastNames = [
        'Mushi', 'Mwakalinga', 'Mashauri', 'Massawe', 'Mkumbo', 'Mhando', 'Mrema', 'Mlay', 'Mwakyusa',
        'Nyerere', 'Mwaipopo', 'Mwambene', 'Mutalemwa', 'Msuya', 'Kassim', 'Bakari', 'Mwangi', 'Achieng',
        'Okello', 'Musoke', 'Nabwire', 'Uwase', 'Mukasa', 'Bizimana', 'Ndayisaba', 'Mutoni', 'Kamau',
        'Kiptoo', 'Mugisha', 'Nshimiyimana', 'Byaruhanga', 'Kasekende', 'Amani', 'Nkundwa', 'Masanja',
    ];

    /**
     * @var list<string>
     */
    private array $tzResidenceSuffixes = [
        'Town', 'City Centre', 'Industrial Area', 'Mlimani', 'Mbezi', 'Kijitonyama', 'Mjini', 'Mabatini',
        'Sokoni', 'Kivukoni', 'Changombe', 'Mbagala', 'Mwanza Central', 'Nzuguni', 'Kisasa',
    ];

    /**
     * @var array<string, list<string>>
     */
    private array $nonTzResidenceMap = [
        'KE' => ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret'],
        'UG' => ['Kampala', 'Entebbe', 'Jinja', 'Mbarara', 'Gulu'],
        'RW' => ['Kigali', 'Musanze', 'Huye', 'Rubavu', 'Rwamagana'],
        'BI' => ['Bujumbura', 'Gitega', 'Ngozi', 'Ruyigi', 'Rumonge'],
        'CD' => ['Goma', 'Bukavu', 'Lubumbashi', 'Kinshasa', 'Kolwezi'],
    ];

    /**
     * @var array<string, list<string>>
     */
    private array $jobTitles = [
        'employed' => [
            'Teacher', 'Nurse', 'Accountant', 'Project Coordinator', 'Bank Officer', 'Procurement Officer',
            'Sales Executive', 'Operations Assistant', 'Customer Care Agent', 'Civil Engineer',
        ],
        'self_employed' => [
            'Business Owner', 'Boutique Owner', 'Salon Owner', 'Photographer', 'Farmer', 'Consultant',
            'Food Vendor', 'Event Planner', 'Graphic Designer', 'Tailor',
        ],
        'student' => [
            'University Student', 'Nursing Student', 'Law Student', 'ICT Student', 'Education Student',
        ],
        'other' => [
            'Freelancer', 'Community Volunteer', 'Part-time Assistant', 'NGO Worker', 'Creative Artist',
        ],
    ];

    public function run(): void
    {
        if ((self::FEMALE_COUNT + self::MALE_COUNT) !== self::TOTAL_MEMBERS) {
            throw new \RuntimeException('Demo member gender counts must total exactly 100.');
        }

        $this->faker = Factory::create();
        $this->faker->seed(20260408);

        $countries = Country::query()
            ->whereIn('iso2', ['TZ', 'KE', 'UG', 'RW', 'BI', 'CD'])
            ->get()
            ->keyBy('iso2');

        $tanzania = $countries->get('TZ');
        if (! $tanzania) {
            $this->command?->warn('DemoMembersSeeder skipped: Tanzania country seed is missing.');

            return;
        }

        $tzRegions = Region::query()
            ->where('country_id', $tanzania->id)
            ->with('districts:id,region_id,name')
            ->get();

        $tzRegionsWithDistricts = $tzRegions
            ->filter(fn (Region $region): bool => $region->districts->isNotEmpty())
            ->values();

        if ($tzRegionsWithDistricts->isEmpty()) {
            $this->command?->warn('DemoMembersSeeder skipped: Tanzania districts are missing.');

            return;
        }

        $this->ensureDemoPhotoAssets();

        DB::transaction(function () use ($countries, $tzRegionsWithDistricts): void {
            $this->deleteExistingDemoMembers();

            $members = array_merge(
                $this->buildMembersForGender(Gender::Female->value, self::FEMALE_COUNT),
                $this->buildMembersForGender(Gender::Male->value, self::MALE_COUNT),
            );

            shuffle($members);

            $countryPlan = $this->buildWeightedPlan([
                'TZ' => 70,
                'KE' => 8,
                'UG' => 6,
                'RW' => 6,
                'BI' => 5,
                'CD' => 5,
            ]);
            $religionPlan = $this->buildWeightedPlan([
                Religion::Christian->value => 45,
                Religion::Muslim->value => 40,
                Religion::Other->value => 15,
            ]);
            $maritalPlan = $this->buildWeightedPlan([
                MaritalStatus::Single->value => 55,
                MaritalStatus::Divorced->value => 20,
                MaritalStatus::Separated->value => 15,
                MaritalStatus::Widowed->value => 10,
            ]);
            $employmentPlan = $this->buildWeightedPlan([
                EmploymentStatus::Employed->value => 42,
                EmploymentStatus::SelfEmployed->value => 24,
                EmploymentStatus::Student->value => 14,
                EmploymentStatus::Unemployed->value => 10,
                EmploymentStatus::Other->value => 10,
            ]);
            $ageBucketPlan = $this->buildWeightedPlan([
                '21-25' => 20,
                '26-30' => 20,
                '31-35' => 20,
                '36-40' => 20,
                '41-47' => 20,
            ]);

            if (
                count($countryPlan) !== self::TOTAL_MEMBERS
                || count($religionPlan) !== self::TOTAL_MEMBERS
                || count($maritalPlan) !== self::TOTAL_MEMBERS
                || count($employmentPlan) !== self::TOTAL_MEMBERS
                || count($ageBucketPlan) !== self::TOTAL_MEMBERS
            ) {
                throw new \RuntimeException('Balanced demo profile plans must contain exactly 100 items each.');
            }

            $tzRegionCursor = 0;

            foreach ($members as $index => $member) {
                $sequence = $index + 1;
                $countryIso = $countryPlan[$index] ?? 'TZ';
                $location = $this->resolveLocation($countryIso, $countries, $tzRegionsWithDistricts, $tzRegionCursor);
                $employmentStatus = $employmentPlan[$index] ?? EmploymentStatus::Employed->value;
                $maritalStatus = $maritalPlan[$index] ?? MaritalStatus::Single->value;
                $hasChildren = $this->hasChildrenForMaritalStatus($maritalStatus, $index);
                $age = $this->ageFromBucket($ageBucketPlan[$index] ?? '31-35');
                $religion = $religionPlan[$index] ?? Religion::Christian->value;

                $user = User::query()->create([
                    'first_name' => $member['first_name'],
                    'last_name' => $member['last_name'],
                    'phone' => sprintf('%s%04d', self::DEMO_PHONE_PREFIX, $sequence),
                    'email' => sprintf('demo.member%03d@%s', $sequence, self::DEMO_EMAIL_DOMAIN),
                    'gender' => $member['gender'],
                    'password' => Hash::make('password123'),
                    'is_admin' => false,
                    'email_verified_at' => now(),
                ]);

                $profile = Profile::factory()
                    ->for($user)
                    ->create([
                        'display_name' => $member['first_name'].' '.$member['last_name'],
                        'age' => $age,
                        'country_id' => $location['country_id'],
                        'region_id' => $location['region_id'],
                        'district_id' => $location['district_id'],
                        'current_residence' => $location['current_residence'],
                        'height_cm' => $member['gender'] === Gender::Male->value
                            ? $this->faker->numberBetween(164, 197)
                            : $this->faker->numberBetween(148, 184),
                        'employment_status' => $employmentStatus,
                        'job_title' => $this->jobTitleForEmployment($employmentStatus),
                        'marital_status' => $maritalStatus,
                        'has_children' => $hasChildren,
                        'children_count' => $hasChildren ? $this->faker->numberBetween(1, 3) : 0,
                        'religion' => $religion,
                        'body_type' => $this->faker->randomElement(array_column(BodyType::cases(), 'value')),
                        'skin_tone' => $this->faker->randomElement(array_column(SkinTone::cases(), 'value')),
                        'about_me' => $this->aboutMeText(),
                        'life_outlook' => $this->lifeOutlookText(),
                        'approval_status' => ProfileApprovalStatus::Approved->value,
                        'is_profile_complete' => true,
                        'is_visible' => true,
                        'approved_by' => null,
                        'approved_at' => now(),
                    ]);

                $religionValue = $profile->religion instanceof Religion
                    ? $profile->religion->value
                    : (string) $profile->religion;

                $this->seedPreference($user, $age, $member['gender'], $religionValue);
                $this->seedPhotos($user->id, $profile->id, $member['gender']);
            }
        });
    }

    private function deleteExistingDemoMembers(): void
    {
        User::query()
            ->where('is_admin', false)
            ->where(function ($query): void {
                $query
                    ->where('email', 'like', '%@'.self::DEMO_EMAIL_DOMAIN)
                    ->orWhere('phone', 'like', self::DEMO_PHONE_PREFIX.'%');
            })
            ->delete();
    }

    /**
     * @return list<array{gender:string,first_name:string,last_name:string}>
     */
    private function buildMembersForGender(string $gender, int $count): array
    {
        $firstNames = $gender === Gender::Female->value ? $this->femaleFirstNames : $this->maleFirstNames;
        $members = [];

        for ($i = 0; $i < $count; $i++) {
            $members[] = [
                'gender' => $gender,
                'first_name' => $this->faker->randomElement($firstNames),
                'last_name' => $this->faker->randomElement($this->lastNames),
            ];
        }

        shuffle($members);

        return $members;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Country>  $countries
     * @param  \Illuminate\Database\Eloquent\Collection<int, Region>  $tzRegionsWithDistricts
     * @return array{country_id:int,region_id:int|null,district_id:int|null,current_residence:string}
     */
    private function resolveLocation(string $countryIso, $countries, $tzRegionsWithDistricts, int &$tzRegionCursor): array
    {
        if (strtoupper($countryIso) === 'TZ') {
            /** @var Region $region */
            $region = $tzRegionsWithDistricts[$tzRegionCursor % $tzRegionsWithDistricts->count()];
            $tzRegionCursor++;
            $district = $region->districts->random();
            $districtName = $district->name;

            return [
                'country_id' => (int) $region->country_id,
                'region_id' => (int) $region->id,
                'district_id' => (int) $district->id,
                'current_residence' => sprintf(
                    '%s, %s',
                    $districtName,
                    $this->faker->randomElement($this->tzResidenceSuffixes)
                ),
            ];
        }

        /** @var Country|null $country */
        $country = $countries->get(strtoupper($countryIso));
        if (! $country) {
            $otherCountries = $countries
                ->filter(fn (Country $model): bool => strtoupper((string) $model->iso2) !== 'TZ')
                ->values();
            $country = $otherCountries->isNotEmpty() ? $otherCountries->random() : $countries->get('TZ');
        }

        if (! $country) {
            throw new \RuntimeException('Unable to resolve country for demo member seeding.');
        }

        $residence = $this->faker->randomElement($this->nonTzResidenceMap[$country->iso2] ?? [$country->name]);

        return [
            'country_id' => (int) $country->id,
            'region_id' => null,
            'district_id' => null,
            'current_residence' => $residence,
        ];
    }

    /**
     * @return list<string>
     */
    private function buildWeightedPlan(array $weights): array
    {
        $plan = [];
        foreach ($weights as $value => $count) {
            for ($i = 0; $i < (int) $count; $i++) {
                $plan[] = (string) $value;
            }
        }

        return $this->faker->shuffleArray($plan);
    }

    private function ageFromBucket(string $bucket): int
    {
        return match ($bucket) {
            '21-25' => $this->faker->numberBetween(21, 25),
            '26-30' => $this->faker->numberBetween(26, 30),
            '31-35' => $this->faker->numberBetween(31, 35),
            '36-40' => $this->faker->numberBetween(36, 40),
            '41-47' => $this->faker->numberBetween(41, 47),
            default => $this->faker->numberBetween(21, 47),
        };
    }

    private function jobTitleForEmployment(string $employmentStatus): ?string
    {
        if ($employmentStatus === EmploymentStatus::Unemployed->value) {
            return null;
        }

        return $this->faker->randomElement($this->jobTitles[$employmentStatus] ?? ['Professional']);
    }

    private function hasChildrenForMaritalStatus(string $maritalStatus, int $index): bool
    {
        return match ($maritalStatus) {
            MaritalStatus::Single->value => $index % 7 === 0,
            MaritalStatus::Divorced->value => $index % 10 <= 6,
            MaritalStatus::Separated->value => $index % 10 <= 5,
            MaritalStatus::Widowed->value => $index % 4 !== 0,
            default => $index % 5 === 0,
        };
    }

    private function aboutMeText(): string
    {
        $personality = $this->faker->randomElement([
            'calm and intentional',
            'warm and family-oriented',
            'driven yet grounded',
            'kind, practical, and honest',
            'friendly and emotionally mature',
        ]);

        $interests = $this->faker->randomElement([
            'weekend road trips, home cooking, and good conversation',
            'community activities, light fitness, and learning new skills',
            'nature walks, podcasts, and quality time with close people',
            'reading, mentorship, and meaningful friendships',
            'music, volunteering, and building healthy routines',
        ]);

        $values = $this->faker->randomElement([
            'I value trust, patience, and consistency in a relationship.',
            'Mutual respect and transparent communication matter a lot to me.',
            'I appreciate kindness, accountability, and emotional safety.',
            'I prefer simple living, shared goals, and a peaceful home life.',
            'I am looking for commitment, growth, and genuine partnership.',
        ]);

        return "I am {$personality}. I enjoy {$interests}. {$values}";
    }

    private function lifeOutlookText(): string
    {
        return $this->faker->randomElement([
            'I believe love grows through daily effort, honesty, and supporting each other through every season.',
            'My long-term goal is to build a stable marriage where both partners feel appreciated and secure.',
            'I value progress over perfection and want a relationship built on teamwork and shared purpose.',
            'For me, partnership means friendship, faithfulness, and making thoughtful decisions together.',
            'I am focused on creating a peaceful future with someone who values growth and commitment.',
        ]);
    }

    private function seedPreference(User $user, int $age, string $gender, string $religion): void
    {
        $preferredGender = $gender === Gender::Female->value
            ? Gender::Male->value
            : Gender::Female->value;

        $minAge = max(18, $age - $this->faker->numberBetween(2, 7));
        $maxAge = min(60, $age + $this->faker->numberBetween(3, 10));

        PartnerPreference::factory()->create([
            'user_id' => $user->id,
            'preferred_gender' => $preferredGender,
            'min_age' => $minAge,
            'max_age' => max($minAge, $maxAge),
            'preferred_religion' => $this->faker->boolean(68) ? $religion : null,
            'must_have_job' => $this->faker->boolean(58),
            'must_be_calm' => $this->faker->boolean(82),
            'must_love_children' => $this->faker->boolean(66),
            'must_be_modest' => $this->faker->boolean(54),
            'must_be_respectful' => true,
            'preferred_skin_tone' => $this->faker->boolean(30)
                ? $this->faker->randomElement(array_column(SkinTone::cases(), 'value'))
                : null,
            'preferred_body_type' => $this->faker->boolean(35)
                ? $this->faker->randomElement(array_column(BodyType::cases(), 'value'))
                : null,
            'additional_notes' => $this->faker->randomElement([
                'Looking for someone intentional and kind.',
                'Open to different backgrounds if values align.',
                'Communication and respect are non-negotiable.',
                'Hoping for a serious partner ready for commitment.',
                'Interested in a calm, supportive, and goal-oriented relationship.',
            ]),
        ]);
    }

    private function seedPhotos(int $userId, int $profileId, string $gender): void
    {
        $variants = $gender === Gender::Female->value
            ? ['female-a.svg', 'female-b.svg', 'female-c.svg']
            : ['male-a.svg', 'male-b.svg', 'male-c.svg'];
        $photoCount = $this->faker->boolean(70) ? 2 : 1;
        $shuffledVariants = $this->faker->shuffleArray($variants);

        for ($i = 0; $i < $photoCount; $i++) {
            $variant = $shuffledVariants[$i % count($shuffledVariants)];
            $folder = $gender === Gender::Female->value ? 'female' : 'male';

            ProfilePhoto::factory()->create([
                'user_id' => $userId,
                'profile_id' => $profileId,
                'path' => "demo/profile-photos/{$folder}/{$variant}",
                'is_primary' => $i === 0,
                'sort_order' => $i + 1,
            ]);
        }
    }

    private function ensureDemoPhotoAssets(): void
    {
        $assets = [
            'demo/profile-photos/female/female-a.svg' => $this->avatarSvg('#f5f8ff', '#e43f6f', 'F'),
            'demo/profile-photos/female/female-b.svg' => $this->avatarSvg('#fff8f4', '#e06d4f', 'F'),
            'demo/profile-photos/female/female-c.svg' => $this->avatarSvg('#f6f9f7', '#4f9f7a', 'F'),
            'demo/profile-photos/male/male-a.svg' => $this->avatarSvg('#f4f8fb', '#2b6cb0', 'M'),
            'demo/profile-photos/male/male-b.svg' => $this->avatarSvg('#f8f7ff', '#5a67d8', 'M'),
            'demo/profile-photos/male/male-c.svg' => $this->avatarSvg('#f6faf8', '#2f855a', 'M'),
        ];

        foreach ($assets as $path => $contents) {
            if (! Storage::disk('local')->exists($path)) {
                Storage::disk('local')->put($path, $contents);
            }
        }
    }

    private function avatarSvg(string $bgColor, string $accentColor, string $letter): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="640" height="640" viewBox="0 0 640 640" role="img" aria-label="GRASA demo avatar">
  <rect width="640" height="640" fill="{$bgColor}" />
  <circle cx="320" cy="250" r="118" fill="{$accentColor}" fill-opacity="0.16"/>
  <circle cx="320" cy="250" r="90" fill="{$accentColor}" fill-opacity="0.34"/>
  <path d="M190 520c22-86 79-130 130-130s108 44 130 130" fill="{$accentColor}" fill-opacity="0.26"/>
  <text x="320" y="336" text-anchor="middle" font-family="Arial, sans-serif" font-size="84" font-weight="700" fill="{$accentColor}">{$letter}</text>
  <text x="320" y="584" text-anchor="middle" font-family="Arial, sans-serif" font-size="26" font-weight="600" fill="#4a5568">GRASA demo</text>
</svg>
SVG;
    }
}
