<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Services\SystemSettingService;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'age' => ['nullable', 'integer', 'min:1', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'district' => ['nullable', 'string', 'max:100'],
            'current_residence' => ['required', 'string', 'max:150'],
            'height' => ['required', 'string', 'max:50'],
            'employment_status' => ['required', 'string', 'max:100'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['required', 'string', 'max:50'],
            'has_children' => ['required', 'boolean'],
            'children_count' => ['required', 'integer', 'min:0', 'max:20'],
            'religion' => ['required', 'string', 'max:100'],
            'body_type' => ['nullable', 'string', 'max:100'],
            'skin_tone' => ['nullable', 'string', 'max:100'],
            'about_me' => ['required', 'string', 'max:3000'],
            'life_outlook' => ['required', 'string', 'max:3000'],
            'is_visible' => ['nullable', 'boolean'],
            'photos' => ['nullable', 'array', 'max:2'],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'primary_photo_id' => ['nullable', 'integer', 'exists:profile_photos,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<string, mixed> $data */
            $data = $this->all();
            $minimumAge = $this->minimumAgeForCurrentUser();
            $minimumAgeMessage = $this->minimumAgeMessage($minimumAge);

            if (empty($data['date_of_birth']) && ! isset($data['age'])) {
                $validator->errors()->add('date_of_birth', 'Date of birth is required.');
            } elseif (! empty($data['date_of_birth'])) {
                try {
                    $calculatedAge = Carbon::parse((string) $data['date_of_birth'])->age;
                    if ($calculatedAge < $minimumAge) {
                        $validator->errors()->add('date_of_birth', $minimumAgeMessage);
                    }
                } catch (\Throwable) {
                    // Date format validation is handled by the date rule.
                }
            } elseif ((int) $data['age'] < $minimumAge) {
                $validator->errors()->add('age', $minimumAgeMessage);
            }
        });
    }

    private function minimumAgeForCurrentUser(): int
    {
        $gender = $this->user()?->gender;

        return app(SystemSettingService::class)->getMinimumAgeForGender($gender);
    }

    private function minimumAgeMessage(int $minimumAge): string
    {
        $gender = $this->user()?->gender;
        $genderValue = $gender instanceof Gender ? $gender->value : strtolower((string) $gender);
        $genderLabel = $genderValue === Gender::Female->value
            ? 'female'
            : ($genderValue === Gender::Male->value ? 'male' : 'selected');

        return "Minimum allowed age for {$genderLabel} members is {$minimumAge} years.";
    }
}
