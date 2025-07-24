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
            'phone_number' => ['required', 'string', 'max:20'],
            'name' => ['nullable', 'string', 'max:255'],
            'problem_description' => ['required', 'string'],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['exists:services,id'],
            'nurse_gender' => ['nullable', 'string', 'in:male,female,any'],
            'time_type' => ['nullable', 'string', 'in:full-time,part-time'],
            'scheduled_time' => ['nullable', 'date', 'after_or_equal:' . now()->subSeconds(30)->toDateTimeString()],
            'ending_time' => ['nullable', 'date', 'after:scheduled_time'],
            'location' => ['required', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            
            // If scheduled_time is provided and it's not immediate (more than 1 minute from now), ending_time is required
            if (!empty($data['scheduled_time'])) {
                $scheduledTime = \Carbon\Carbon::parse($data['scheduled_time']);
                $minutesDiff = abs($scheduledTime->diffInMinutes(now()));
                
                // If scheduled time is more than 1 minute from now (future), it's a scheduled appointment
                $isScheduled = $scheduledTime->gt(now()->addMinute());
                
                if ($isScheduled && empty($data['ending_time'])) {
                    $validator->errors()->add('ending_time', 'Ending time is required for scheduled appointments.');
                }
            }
        });
    }


    public function messages(): array
    {
        return [
            'service_ids.required' => 'At least one service must be selected.',
            'service_ids.min' => 'At least one service must be selected.',
            'service_ids.*.exists' => 'One or more selected services are invalid.',

            'ending_time.after' => 'Ending time must be after scheduled time.',
        ];
    }
} 