<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePartnerPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
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
            'preferred_gender' => ['required', Rule::in(array_column(Gender::cases(), 'value'))],
            'min_age' => ['required', 'integer', 'min:18', 'max:80'],
            'max_age' => ['required', 'integer', 'min:18', 'max:80', 'gte:min_age'],
            'preferred_religion' => ['nullable', 'string', 'max:100'],
            'must_have_job' => ['sometimes', 'boolean'],
            'must_be_calm' => ['sometimes', 'boolean'],
            'must_love_children' => ['sometimes', 'boolean'],
            'must_be_modest' => ['sometimes', 'boolean'],
            'must_be_respectful' => ['sometimes', 'boolean'],
            'preferred_skin_tone' => ['nullable', 'string', 'max:100'],
            'preferred_body_type' => ['nullable', 'string', 'max:100'],
            'additional_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
