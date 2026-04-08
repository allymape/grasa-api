<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UploadProfilePhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'photo' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}

