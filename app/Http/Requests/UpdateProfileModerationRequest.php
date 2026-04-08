<?php

namespace App\Http\Requests;

use App\Enums\ProfileApprovalStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileModerationRequest extends FormRequest
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
            'approval_status' => [
                'required',
                Rule::in([
                    ProfileApprovalStatus::Approved->value,
                    ProfileApprovalStatus::Rejected->value,
                ]),
            ],
            'is_visible' => ['sometimes', 'boolean'],
        ];
    }
}
