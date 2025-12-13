<?php

namespace App\Http\Requests;

use App\Models\Request;
use App\Services\CategoryHandlers\CategoryRequestHandlerFactory;
use Illuminate\Foundation\Http\FormRequest;

class CreateRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization will be handled by policies
    }

    public function rules(): array
    {
        // Get category_id from request (defaults to 1: Service Request)
        $categoryId = $this->input('category_id', 1);
        
        // Get category-specific validation rules
        try {
            return CategoryRequestHandlerFactory::getValidationRules($categoryId);
        } catch (\InvalidArgumentException $e) {
            // If category handler doesn't exist, return basic rules
            return [
                'category_id' => ['required', 'integer', 'exists:categories,id'],
                'first_name' => ['nullable', 'string', 'max:255'],
                'last_name' => ['nullable', 'string', 'max:255'],
                'phone_number' => ['nullable', 'string', 'max:20'],
                'problem_description' => ['nullable', 'string'],
                'nurse_gender' => ['nullable', 'string', 'in:male,female,any'],
            ];
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();
            $categoryId = $data['category_id'] ?? 1;
            
            // Category 1 specific validations
            if ($categoryId === 1) {
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
            }
            
            // Add category-specific validations here for other categories when implemented
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