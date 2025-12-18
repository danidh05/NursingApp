<?php

namespace App\Services\CategoryHandlers;

use App\DTOs\Request\CreateRequestDTO;

/**
 * Category 4: Machines Handler
 * 
 * This category supports:
 * - machine_id (required)
 * - from_date (optional, Category 4 only)
 * - to_date (optional, Category 4 only)
 * - Address fields (same as Category 1)
 */
class Category4RequestHandler extends BaseCategoryRequestHandler
{
    public function getValidationRules(): array
    {
        return array_merge($this->getCommonRules(), [
            // Category 4: Machines specific fields
            'machine_id' => ['required', 'integer', 'exists:machines,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'from_date' => ['nullable', 'date', 'after_or_equal:today'],
            'to_date' => ['nullable', 'date', 'after:from_date'],
            'additional_information' => ['nullable', 'string'],
            // Address fields (same as Category 1)
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
            service_id: null, // Category 4 doesn't use service_id
            area_id: $data['area_id'] ?? null, // Category 4 uses area-based pricing
            category_id: 4, // Category 4: Machines
            name: $data['name'] ?? null,
            nurse_gender: $data['nurse_gender'] ?? null,
            time_type: null, // Not used for Category 4
            scheduled_time: null, // Not used for Category 4
            ending_time: null, // Not used for Category 4
            location: $data['location'] ?? null,
            // Address fields
            use_saved_address: $this->normalizeBoolean($data['use_saved_address'] ?? false),
            address_city: $data['address_city'] ?? null,
            address_street: $data['address_street'] ?? null,
            address_building: $data['address_building'] ?? null,
            address_additional_information: $data['address_additional_information'] ?? null,
            // Category 2 fields (not used for Category 4)
            test_package_id: null,
            test_id: null,
            request_details_files: null,
            notes: null,
            request_with_insurance: null,
            attach_front_face: null,
            attach_back_face: null,
            // Category 3 fields (not used for Category 4)
            ray_id: null,
            // Category 4 specific fields
            machine_id: $data['machine_id'] ?? null,
            from_date: isset($data['from_date']) ? $data['from_date'] : null,
            to_date: isset($data['to_date']) ? $data['to_date'] : null,
            additional_information: $data['additional_information'] ?? null,
        );
    }
}
