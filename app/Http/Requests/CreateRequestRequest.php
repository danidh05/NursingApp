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
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'nurse_gender' => ['nullable', 'string', 'in:male,female,any'],
            'time_type' => ['nullable', 'string', 'in:full-time,part-time'],
            'scheduled_time' => ['nullable', 'date', 'after_or_equal:' . now()->subSeconds(30)->toDateTimeString()],
            'ending_time' => ['nullable', 'date', 'after:scheduled_time'],
            'location' => ['nullable', 'string'], // Can be coordinates or address string
            // Address fields
            'use_saved_address' => ['nullable', 'boolean'],
            'address_city' => ['nullable', 'required_without:use_saved_address', 'string', 'max:255'],
            'address_street' => ['nullable', 'required_without:use_saved_address', 'string', 'max:255'],
            'address_building' => ['nullable', 'string', 'max:255'],
            'address_additional_information' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            
            // Address validation: if use_saved_address is false, new address fields are required
            if (isset($data['use_saved_address']) && !$data['use_saved_address']) {
                if (empty($data['address_city'])) {
                    $validator->errors()->add('address_city', 'City is required when not using saved address.');
                }
                if (empty($data['address_street'])) {
                    $validator->errors()->add('address_street', 'Street is required when not using saved address.');
                }
            }
            
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

            // Validate that the selected area has pricing for all requested services
            // If area_id is not provided, use the authenticated user's registered area
            $areaId = $data['area_id'] ?? auth()->user()?->area_id;
            
            if (!empty($areaId) && !empty($data['service_ids'])) {
                $serviceIds = $data['service_ids'];
                
                $missingPricing = \App\Models\ServiceAreaPrice::whereIn('service_id', $serviceIds)
                    ->where('area_id', $areaId)
                    ->pluck('service_id')
                    ->toArray();
                
                $missingServices = array_diff($serviceIds, $missingPricing);
                
                if (!empty($missingServices)) {
                    $validator->errors()->add('area_id', 'The selected area does not have pricing configured for all requested services.');
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