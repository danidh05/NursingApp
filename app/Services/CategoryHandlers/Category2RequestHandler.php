<?php

namespace App\Services\CategoryHandlers;

use App\DTOs\Request\CreateRequestDTO;

/**
 * Category 2: Tests Handler
 * 
 * This category supports:
 * - test_package_id OR test_id (one is required, not both)
 * - request_details_files (optional, array of files)
 * - notes (optional)
 * - request_with_insurance (optional, boolean)
 * - attach_front_face, attach_back_face (optional, files)
 * - Address fields (same as Category 1)
 */
class Category2RequestHandler extends BaseCategoryRequestHandler
{
    public function getValidationRules(): array
    {
        return array_merge($this->getCommonRules(), [
            // Tests specific fields - either test_package_id OR test_id (not both)
            // Note: The "not both" logic is handled in CreateRequestRequest::withValidator()
            'test_package_id' => ['nullable', 'integer', 'exists:test_packages,id', 'required_without:test_id'],
            'test_id' => ['nullable', 'integer', 'exists:tests,id', 'required_without:test_package_id'],
            'request_details_files' => ['nullable', 'array'],
            'request_details_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB max
            'notes' => ['nullable', 'string'],
            'request_with_insurance' => ['nullable', 'boolean'],
            'attach_front_face' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'attach_back_face' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
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
            service_id: null, // Category 2 doesn't use service_id
            area_id: null, // Category 2 doesn't use area pricing
            category_id: $data['category_id'] ?? 2,
            // Category 2 specific fields (file paths already processed in RequestService)
            test_package_id: $data['test_package_id'] ?? null,
            test_id: $data['test_id'] ?? null, // For individual test requests
            request_details_files: $data['request_details_files'] ?? null, // Array of file paths
            notes: $data['notes'] ?? null,
            request_with_insurance: $this->normalizeBoolean($data['request_with_insurance'] ?? false),
            attach_front_face: $data['attach_front_face'] ?? null, // File path
            attach_back_face: $data['attach_back_face'] ?? null, // File path
            additional_information: $data['additional_information'] ?? null,
            // Address fields
            use_saved_address: $this->normalizeBoolean($data['use_saved_address'] ?? false),
            address_city: $data['address_city'] ?? null,
            address_street: $data['address_street'] ?? null,
            address_building: $data['address_building'] ?? null,
            address_additional_information: $data['address_additional_information'] ?? null,
        );
    }
}

