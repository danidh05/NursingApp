<?php

namespace App\Http\Requests;

use App\Models\Request;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization will be handled by policies
    }

    public function rules(): array
    {
        return [
            'full_name' => ['sometimes', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'string', 'max:15'],
            'location' => ['sometimes', 'string'],
            'time_type' => ['sometimes', 'string', 'in:' . implode(',', Request::getValidTimeTypes())],
            'nurse_gender' => ['sometimes', 'string', 'in:male,female'],
            'service_ids' => ['sometimes', 'array'],
            'service_ids.*' => ['required_with:service_ids', 'exists:services,id'],
            'problem_description' => ['nullable', 'string', 'max:1000'],
            'scheduled_time' => ['sometimes', 'date'],
            'ending_time' => ['nullable', 'date', 'after:scheduled_time'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', Request::getValidStatuses())],
            'nurse_id' => ['sometimes', 'exists:nurses,id'],
            'time_needed_to_arrive' => ['nullable', 'integer', 'min:1'],
            'discount_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_ids.*.exists' => 'One or more selected services are invalid.',
            'ending_time.after' => 'End time must be after scheduled time.',
            'status.in' => 'Invalid status provided.',
            'nurse_id.exists' => 'Selected nurse does not exist.',
        ];
    }
} 