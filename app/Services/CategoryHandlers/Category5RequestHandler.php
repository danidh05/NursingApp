<?php

namespace App\Services\CategoryHandlers;

use App\DTOs\Request\CreateRequestDTO;

/**
 * Category 5: Physiotherapists Handler
 * 
 * This category supports:
 * - physiotherapist_id (required)
 * - area_id (optional, for area-based pricing)
 * - sessions_per_month (required, int)
 * - from_date (optional)
 * - to_date (optional)
 * - machines_included (boolean)
 * - physio_machines (array of machine IDs with their data)
 * - request_details (PDF file)
 * - notes (string)
 * - Address fields (same as Category 1)
 */
class Category5RequestHandler extends BaseCategoryRequestHandler
{
    public function getValidationRules(): array
    {
        return array_merge($this->getCommonRules(), [
            // Category 5: Physiotherapists specific fields
            'physiotherapist_id' => ['required', 'integer', 'exists:physiotherapists,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'sessions_per_month' => ['required', 'integer', 'min:1'],
            'from_date' => ['nullable', 'date', 'after_or_equal:today'],
            'to_date' => ['nullable', 'date', 'after:from_date'],
            'machines_included' => ['nullable', 'boolean'],
            'physio_machines' => ['nullable', 'array'], // Array of physio machine IDs
            'physio_machines.*' => ['integer', 'exists:physio_machines,id'],
            'request_details' => ['nullable', 'file', 'mimes:pdf', 'max:5120'], // PDF file, 5MB max
            'notes' => ['nullable', 'string'],
            'additional_information' => ['nullable', 'string'],
            'total_price' => ['nullable', 'numeric', 'min:0'], // Frontend-calculated total price
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
        // Handle request_details file upload (will be converted to path in service)
        $requestDetailsPath = null;
        if (isset($data['request_details']) && is_string($data['request_details'])) {
            $requestDetailsPath = $data['request_details'];
        }

        // Handle physio_machines array - ensure it's an array of integers
        $physioMachines = null;
        if (isset($data['physio_machines'])) {
            if (is_array($data['physio_machines'])) {
                // Filter to ensure only integers (machine IDs)
                $physioMachines = array_filter($data['physio_machines'], 'is_int');
                $physioMachines = !empty($physioMachines) ? array_values($physioMachines) : null;
            } elseif (is_string($data['physio_machines'])) {
                // Try to decode JSON string
                $decoded = json_decode($data['physio_machines'], true);
                if (is_array($decoded)) {
                    $physioMachines = array_filter($decoded, 'is_int');
                    $physioMachines = !empty($physioMachines) ? array_values($physioMachines) : null;
                }
            }
        }

        return new CreateRequestDTO(
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            full_name: $this->buildFullName($data) ?? $data['full_name'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            problem_description: $data['problem_description'] ?? null,
            service_id: null, // Category 5 doesn't use service_id
            area_id: $data['area_id'] ?? null, // Category 5 uses area-based pricing
            category_id: 5, // Category 5: Physiotherapists
            name: $data['name'] ?? null,
            nurse_gender: $data['nurse_gender'] ?? null,
            time_type: null, // Not used for Category 5
            scheduled_time: null, // Not used for Category 5
            ending_time: null, // Not used for Category 5
            location: $data['location'] ?? null,
            // Address fields
            use_saved_address: $this->normalizeBoolean($data['use_saved_address'] ?? false),
            address_city: $data['address_city'] ?? null,
            address_street: $data['address_street'] ?? null,
            address_building: $data['address_building'] ?? null,
            address_additional_information: $data['address_additional_information'] ?? null,
            // Category 2 fields (not used for Category 5)
            test_package_id: null,
            test_id: null,
            request_details_files: $requestDetailsPath ? [$requestDetailsPath] : null, // Single PDF file as array
            notes: $data['notes'] ?? null,
            request_with_insurance: null,
            attach_front_face: null,
            attach_back_face: null,
            // Category 3 fields (not used for Category 5)
            ray_id: null,
            // Category 4 fields (not used for Category 5)
            machine_id: null,
            from_date: isset($data['from_date']) ? $data['from_date'] : null,
            to_date: isset($data['to_date']) ? $data['to_date'] : null,
            // Category 5 specific fields
            physiotherapist_id: $data['physiotherapist_id'] ?? null,
            sessions_per_month: isset($data['sessions_per_month']) ? (int)$data['sessions_per_month'] : null,
            machines_included: $this->normalizeBoolean($data['machines_included'] ?? false),
            physio_machines: $physioMachines,
            additional_information: $data['additional_information'] ?? null,
        );
    }
}
