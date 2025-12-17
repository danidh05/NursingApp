<?php

namespace App\Services\CategoryHandlers;

use App\DTOs\Request\CreateRequestDTO;

/**
 * Category 3: Rays Handler
 * 
 * This category supports:
 * - ray_id (required)
 * - notes (optional)
 * - request_details_files (optional, PDF file)
 * - Address fields (same as Category 1)
 */
class Category3RequestHandler extends BaseCategoryRequestHandler
{
    public function getValidationRules(): array
    {
        return array_merge($this->getCommonRules(), [
            // Category 3: Rays specific fields
            'ray_id' => ['required', 'integer', 'exists:rays,id'],
            'notes' => ['nullable', 'string'],
            // Accept either a single file or an array of files (PDF)
            'request_details_files' => ['nullable'], // Can be file or array, validated separately
            'request_details_files.*' => ['file', 'mimes:pdf', 'max:5120'], // 5MB max, PDF only
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
        // Ensure request_details_files is an array of paths (not UploadedFile objects)
        $requestDetailsFiles = null;
        if (isset($data['request_details_files'])) {
            if (is_array($data['request_details_files'])) {
                // Filter out any UploadedFile objects, keep only strings (paths)
                $requestDetailsFiles = array_filter($data['request_details_files'], function ($item) {
                    return is_string($item);
                });
                $requestDetailsFiles = !empty($requestDetailsFiles) ? array_values($requestDetailsFiles) : null;
            } elseif (is_string($data['request_details_files'])) {
                $requestDetailsFiles = [$data['request_details_files']];
            }
        }

        return new CreateRequestDTO(
            user_id: $data['user_id'],
            category_id: 3, // Category 3: Rays
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            full_name: $data['full_name'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            name: $data['name'] ?? null,
            problem_description: $data['problem_description'] ?? null,
            status: $data['status'] ?? 'submitted',
            nurse_gender: $data['nurse_gender'] ?? null,
            time_type: null, // Not used for Category 3
            scheduled_time: null, // Not used for Category 3
            ending_time: null, // Not used for Category 3
            location: $data['location'] ?? null,
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
            total_price: $data['total_price'] ?? null,
            discount_percentage: $data['discount_percentage'] ?? null,
            discounted_price: $data['discounted_price'] ?? null,
            use_saved_address: $data['use_saved_address'] ?? null,
            address_city: $data['address_city'] ?? null,
            address_street: $data['address_street'] ?? null,
            address_building: $data['address_building'] ?? null,
            address_additional_information: $data['address_additional_information'] ?? null,
            additional_information: $data['additional_information'] ?? null,
            area_id: null, // Not used for Category 3
            service_id: null, // Not used for Category 3
            // Category 3 specific fields
            ray_id: $data['ray_id'] ?? null,
            notes: $data['notes'] ?? null,
            request_details_files: $requestDetailsFiles,
            // Category 2 fields (not used for Category 3)
            test_package_id: null,
            test_id: null,
            request_with_insurance: null,
            attach_front_face: null,
            attach_back_face: null,
        );
    }
}
