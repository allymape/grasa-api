<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'age' => ['required', 'integer', 'min:18', 'max:80'],
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
}
