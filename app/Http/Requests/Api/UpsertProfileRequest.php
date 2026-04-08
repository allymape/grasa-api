<?php

namespace App\Http\Requests\Api;

use App\Enums\BodyType;
use App\Enums\EmploymentStatus;
use App\Enums\MaritalStatus;
use App\Enums\Religion;
use App\Enums\SkinTone;
use App\Models\Country;
use App\Models\District;
use App\Models\Region;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'display_name' => ['required', 'string', 'max:100'],
            'age' => ['required', 'integer', 'min:18', 'max:80'],
            'country_id' => ['required', 'integer', 'exists:countries,id'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'district_id' => ['nullable', 'integer', 'exists:districts,id'],
            'current_residence' => ['required', 'string', 'max:150'],
            'height_cm' => ['required', 'integer', 'min:100', 'max:250'],
            'employment_status' => ['required', Rule::in(array_column(EmploymentStatus::cases(), 'value'))],
            'job_title' => ['nullable', 'string', 'max:100'],
            'marital_status' => ['required', Rule::in(array_column(MaritalStatus::cases(), 'value'))],
            'has_children' => ['required', 'boolean'],
            'children_count' => ['required', 'integer', 'min:0', 'max:20'],
            'religion' => ['required', Rule::in(array_column(Religion::cases(), 'value'))],
            'body_type' => ['nullable', Rule::in(array_column(BodyType::cases(), 'value'))],
            'skin_tone' => ['nullable', Rule::in(array_column(SkinTone::cases(), 'value'))],
            'about_me' => ['required', 'string', 'max:3000'],
            'life_outlook' => ['required', 'string', 'max:3000'],
            'is_visible' => ['sometimes', 'boolean'],
            'photos' => ['sometimes', 'array', 'max:2'],
            'photos.*.path' => ['required_with:photos', 'string', 'max:255'],
            'photos.*.is_primary' => ['sometimes', 'boolean'],
            'photos.*.sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<string, mixed> $data */
            $data = $this->all();

            $countryId = $data['country_id'] ?? null;
            $regionId = $data['region_id'] ?? null;
            $districtId = $data['district_id'] ?? null;

            $country = null;
            if ($countryId) {
                $country = Country::query()->find($countryId);
            }

            if ($country?->requires_region_district) {
                if (! $regionId) {
                    $validator->errors()->add('region_id', 'Region is required for the selected country.');
                }
                if (! $districtId) {
                    $validator->errors()->add('district_id', 'District is required for the selected country.');
                }
            }

            if ($regionId) {
                $region = Region::query()->select(['id', 'country_id'])->find($regionId);
                if (! $region || (int) $region->country_id !== (int) $countryId) {
                    $validator->errors()->add('region_id', 'The selected region does not belong to the selected country.');
                }
            }

            if ($districtId) {
                if (! $regionId) {
                    $validator->errors()->add('region_id', 'Region is required when district is provided.');
                } else {
                    $district = District::query()->select(['id', 'region_id'])->find($districtId);
                    if (! $district || (int) $district->region_id !== (int) $regionId) {
                        $validator->errors()->add('district_id', 'The selected district does not belong to the selected region.');
                    }
                }
            }

            $hasChildren = filter_var(
                $data['has_children'] ?? false,
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
            if ($hasChildren === false && (int) ($data['children_count'] ?? 0) > 0) {
                $validator->errors()->add('children_count', 'Children count must be zero when has_children is false.');
            }

            if ($hasChildren === true && (int) ($data['children_count'] ?? 0) <= 0) {
                $validator->errors()->add('children_count', 'Children count must be greater than zero when has_children is true.');
            }

            if (array_key_exists('photos', $data) && is_array($data['photos'])) {
                $primaryCount = collect($data['photos'])
                    ->filter(fn (mixed $photo): bool => is_array($photo) && (bool) ($photo['is_primary'] ?? false))
                    ->count();

                if ($primaryCount > 1) {
                    $validator->errors()->add('photos', 'Only one photo can be marked as primary.');
                }
            }
        });
    }
}
