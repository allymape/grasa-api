<?php

namespace Database\Seeders;

use App\Enums\EmploymentStatus;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use App\Enums\ProfileApprovalStatus;
use App\Enums\Religion;
use App\Models\Country;
use App\Models\District;
use App\Models\PartnerPreference;
use App\Models\Profile;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SampleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $tz = Country::query()->where('iso2', 'TZ')->first();
        $ke = Country::query()->where('iso2', 'KE')->first() ?? $tz;

        if (! $tz || ! $ke) {
            return;
        }

        $tzRegion = Region::query()->where('country_id', $tz->id)->orderBy('id')->first();
        $tzDistrict = $tzRegion
            ? District::query()->where('region_id', $tzRegion->id)->orderBy('id')->first()
            : null;

        $aisha = $this->createOrUpdateUser([
            'first_name' => 'Aisha',
            'last_name' => 'Mashauri',
            'phone' => '0712000100',
            'email' => 'aisha@example.com',
            'gender' => Gender::Female->value,
        ]);

        $brian = $this->createOrUpdateUser([
            'first_name' => 'Brian',
            'last_name' => 'Mwangi',
            'phone' => '0712000200',
            'email' => 'brian@example.com',
            'gender' => Gender::Male->value,
        ]);

        Profile::query()->updateOrCreate(
            ['user_id' => $aisha->id],
            [
                'display_name' => 'Aisha M',
                'age' => 27,
                'country_id' => $tz->id,
                'region_id' => $tzRegion?->id,
                'district_id' => $tzDistrict?->id,
                'current_residence' => 'Dar es Salaam',
                'height_cm' => 165,
                'employment_status' => EmploymentStatus::Employed->value,
                'job_title' => 'Teacher',
                'marital_status' => MaritalStatus::Single->value,
                'has_children' => false,
                'children_count' => 0,
                'religion' => Religion::Muslim->value,
                'body_type' => null,
                'skin_tone' => null,
                'about_me' => 'Kind, family-oriented, and values clear communication.',
                'life_outlook' => 'Build a peaceful home with trust and growth.',
                'approval_status' => ProfileApprovalStatus::Approved->value,
                'is_profile_complete' => true,
                'is_visible' => true,
            ]
        );

        Profile::query()->updateOrCreate(
            ['user_id' => $brian->id],
            [
                'display_name' => 'Brian M',
                'age' => 30,
                'country_id' => $ke->id,
                'region_id' => null,
                'district_id' => null,
                'current_residence' => 'Nairobi',
                'height_cm' => 176,
                'employment_status' => EmploymentStatus::SelfEmployed->value,
                'job_title' => 'Business Owner',
                'marital_status' => MaritalStatus::Single->value,
                'has_children' => false,
                'children_count' => 0,
                'religion' => Religion::Christian->value,
                'body_type' => null,
                'skin_tone' => null,
                'about_me' => 'Respectful, hardworking, and ready for serious commitment.',
                'life_outlook' => 'Partnership built on mutual support and honesty.',
                'approval_status' => ProfileApprovalStatus::Approved->value,
                'is_profile_complete' => true,
                'is_visible' => true,
            ]
        );

        PartnerPreference::query()->updateOrCreate(
            ['user_id' => $aisha->id],
            [
                'preferred_gender' => Gender::Male->value,
                'min_age' => 27,
                'max_age' => 38,
                'preferred_religion' => null,
                'must_have_job' => true,
                'must_be_calm' => true,
                'must_love_children' => false,
                'must_be_modest' => true,
                'must_be_respectful' => true,
                'preferred_skin_tone' => null,
                'preferred_body_type' => null,
                'additional_notes' => 'Honest communication matters most.',
            ]
        );

        PartnerPreference::query()->updateOrCreate(
            ['user_id' => $brian->id],
            [
                'preferred_gender' => Gender::Female->value,
                'min_age' => 24,
                'max_age' => 34,
                'preferred_religion' => null,
                'must_have_job' => false,
                'must_be_calm' => true,
                'must_love_children' => true,
                'must_be_modest' => true,
                'must_be_respectful' => true,
                'preferred_skin_tone' => null,
                'preferred_body_type' => null,
                'additional_notes' => 'Looking for a genuine long-term match.',
            ]
        );
    }

    /**
     * @param  array{first_name:string,last_name:string,phone:string,email:string,gender:string}  $attributes
     */
    private function createOrUpdateUser(array $attributes): User
    {
        $user = User::query()->firstOrNew(['phone' => $attributes['phone']]);
        $user->fill([
            'first_name' => $attributes['first_name'],
            'last_name' => $attributes['last_name'],
            'email' => $attributes['email'],
            'gender' => $attributes['gender'],
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);

        if (! $user->exists || ! Hash::check('password123', (string) $user->password)) {
            $user->password = Hash::make('password123');
        }

        $user->save();

        return $user;
    }
}
