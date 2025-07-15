<?php

namespace App\Http\Requests;

use App\Models\Request;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization will be handled by policies
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:15'],
            'location' => ['required', 'string'],
            'time_type' => ['required', 'string', 'in:' . implode(',', Request::getValidTimeTypes())],
            'nurse_gender' => ['required', 'string', 'in:male,female'],
            'service_ids' => ['required', 'array'],
            'service_ids.*' => ['required', 'exists:services,id'],
            'problem_description' => ['nullable', 'string', 'max:1000'],
            'scheduled_time' => ['required', 'date'],
            'ending_time' => ['nullable', 'date', 'after:scheduled_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_ids.required' => 'At least one service must be selected.',
            'service_ids.*.exists' => 'One or more selected services are invalid.',
            'ending_time.after' => 'End time must be after scheduled time.',
        ];
    }
} 