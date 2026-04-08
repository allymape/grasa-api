<?php

namespace App\Http\Requests\Api;

use App\Enums\BodyType;
use App\Enums\Gender;
use App\Enums\Religion;
use App\Enums\SkinTone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPartnerPreferenceRequest extends FormRequest
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
            'preferred_gender' => ['required', Rule::in(array_column(Gender::cases(), 'value'))],
            'min_age' => ['required', 'integer', 'min:18', 'max:80'],
            'max_age' => ['required', 'integer', 'min:18', 'max:80', 'gte:min_age'],
            'preferred_religion' => ['nullable', Rule::in(array_column(Religion::cases(), 'value'))],
            'must_have_job' => ['sometimes', 'boolean'],
            'must_be_calm' => ['sometimes', 'boolean'],
            'must_love_children' => ['sometimes', 'boolean'],
            'must_be_modest' => ['sometimes', 'boolean'],
            'must_be_respectful' => ['sometimes', 'boolean'],
            'preferred_skin_tone' => ['nullable', Rule::in(array_column(SkinTone::cases(), 'value'))],
            'preferred_body_type' => ['nullable', Rule::in(array_column(BodyType::cases(), 'value'))],
            'additional_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
