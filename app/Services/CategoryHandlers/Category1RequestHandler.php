<?php

namespace App\Services\CategoryHandlers;

use App\DTOs\Request\CreateRequestDTO;

/**
 * Category 1: Service Request Handler
 * 
 * This category requires:
 * - service_id (integer, required) - Only one service per request
 * - area_id (optional, uses user's area if not provided)
 * - Address fields (required if use_saved_address is false)
 * - time_type, scheduled_time, ending_time (optional)
 */
class Category1RequestHandler extends BaseCategoryRequestHandler
{
    public function getValidationRules(): array
    {
        return array_merge($this->getCommonRules(), [
            // Service Request specific fields
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'time_type' => ['nullable', 'string', 'in:full-time,part-time'],
            'scheduled_time' => ['nullable', 'date', 'after_or_equal:' . now()->subSeconds(30)->toDateTimeString()],
            'ending_time' => ['nullable', 'date', 'after:scheduled_time'],
            'location' => ['nullable', 'string'],
            // Address fields (required for Category 1)
            'use_saved_address' => ['nullable', 'boolean'],
            'address_city' => ['nullable', 'required_without:use_saved_address', 'string', 'max:255'],
            'address_street' => ['nullable', 'required_without:use_saved_address', 'string', 'max:255'],
            'address_building' => ['nullable', 'string', 'max:255'],
            'address_additional_information' => ['nullable', 'string'],
        ]);
    }

    public function mapToDTO(array $data): CreateRequestDTO
    {
        return new CreateRequestDTO(
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            full_name: $this->buildFullName($data) ?? $data['full_name'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            problem_description: $data['problem_description'] ?? null,
            service_id: $data['service_id'] ?? null,
            area_id: $data['area_id'] ?? null,
            category_id: $data['category_id'] ?? 1,
            name: $data['name'] ?? null,
            nurse_gender: $data['nurse_gender'] ?? null,
            time_type: $data['time_type'] ?? null,
            scheduled_time: $data['scheduled_time'] ?? null,
            ending_time: $data['ending_time'] ?? null,
            location: $data['location'] ?? null,
            use_saved_address: $data['use_saved_address'] ?? false,
            address_city: $data['address_city'] ?? null,
            address_street: $data['address_street'] ?? null,
            address_building: $data['address_building'] ?? null,
            address_additional_information: $data['address_additional_information'] ?? null,
        );
    }
}

