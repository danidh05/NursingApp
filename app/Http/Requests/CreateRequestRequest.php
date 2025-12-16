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

    /**
     * Prepare the data for validation.
     * Normalize boolean strings from form-data before validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize boolean strings from form-data
        $this->merge([
            'use_saved_address' => $this->normalizeBooleanString($this->input('use_saved_address')),
            'request_with_insurance' => $this->normalizeBooleanString($this->input('request_with_insurance')),
        ]);
    }

    /**
     * Normalize boolean string values from form-data.
     * Converts "true", "1", "on", "yes" to true, everything else to false.
     *
     * @param mixed $value
     * @return bool|null
     */
    private function normalizeBooleanString($value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', '1', 'on', 'yes'], true);
        }
        
        return (bool) $value;
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
            
            // Category 2 specific validations
            if ($categoryId === 2) {
                // Ensure either test_package_id OR test_id is provided, but not both
                $hasTestPackage = !empty($data['test_package_id']);
                $hasTest = !empty($data['test_id']);
                
                if (!$hasTestPackage && !$hasTest) {
                    $validator->errors()->add('test_package_id', 'Either test_package_id or test_id must be provided for Category 2 requests.');
                    $validator->errors()->add('test_id', 'Either test_package_id or test_id must be provided for Category 2 requests.');
                }
                
                if ($hasTestPackage && $hasTest) {
                    $validator->errors()->add('test_package_id', 'Cannot provide both test_package_id and test_id. Please provide only one.');
                    $validator->errors()->add('test_id', 'Cannot provide both test_package_id and test_id. Please provide only one.');
                }
            }
            
            // Category 1 specific validations
            if ($categoryId === 1) {
                // Normalize boolean from form-data (may be string "true"/"false")
                $useSavedAddress = filter_var($data['use_saved_address'] ?? false, FILTER_VALIDATE_BOOLEAN);
                
                // Address validation: if use_saved_address is false, new address fields are required
                if (!$useSavedAddress) {
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

                // Validate that the selected area has pricing for the requested service (Category 1 only)
                // If area_id is not provided, use the authenticated user's registered area
                $areaId = $data['area_id'] ?? auth()->user()?->area_id;
                
                if (!empty($areaId) && !empty($data['service_id'])) {
                    $serviceId = $data['service_id'];
                    
                    $hasPricing = \App\Models\ServiceAreaPrice::where('service_id', $serviceId)
                        ->where('area_id', $areaId)
                        ->exists();
                    
                    if (!$hasPricing) {
                        $validator->errors()->add('area_id', 'The selected area does not have pricing configured for the requested service.');
                    }
                }
            }
            
            // Add category-specific validations here for other categories when implemented
        });
    }


    public function messages(): array
    {
        return [
            'service_id.required' => 'A service must be selected for Category 1 requests.',
            'service_id.exists' => 'The selected service is invalid.',

            'ending_time.after' => 'Ending time must be after scheduled time.',
        ];
    }
} 