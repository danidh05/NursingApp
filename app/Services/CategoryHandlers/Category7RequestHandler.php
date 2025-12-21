<?php

namespace App\Services\CategoryHandlers;

use App\DTOs\Request\CreateRequestDTO;

/**
 * Category 7: Duties Handler
 * 
 * This category supports 3 subcategories:
 * 1. Nurse Visits: nurse_visit_id, visits_per_day (1-4)
 * 2. Duties: duty_id, duration_hours (4,6,8,12,24 or null for continuous), is_continuous_care, is_day_shift
 * 3. Babysitter: babysitter_id, duration_hours (12 or 24), is_day_shift
 * 
 * Common fields:
 * - from_date, to_date (required)
 * - request_details (PDF file)
 * - notes (string)
 * - Address fields (same as Category 1)
 */
class Category7RequestHandler extends BaseCategoryRequestHandler
{
    public function getValidationRules(): array
    {
        return array_merge($this->getCommonRules(), [
            // Category 7: Duties - one of the subcategory IDs is required
            'nurse_visit_id' => ['nullable', 'integer', 'exists:nurse_visits,id', 'required_without_all:duty_id,babysitter_id'],
            'duty_id' => ['nullable', 'integer', 'exists:duties,id', 'required_without_all:nurse_visit_id,babysitter_id'],
            'babysitter_id' => ['nullable', 'integer', 'exists:babysitters,id', 'required_without_all:nurse_visit_id,duty_id'],
            
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            
            // Common fields for all subcategories
            'from_date' => ['required', 'date', 'after_or_equal:today'],
            'to_date' => ['required', 'date', 'after:from_date'],
            'request_details' => ['nullable', 'file', 'mimes:pdf', 'max:5120'], // PDF file, 5MB max
            'notes' => ['nullable', 'string'],
            'additional_information' => ['nullable', 'string'],
            
            // Nurse Visits specific
            'visits_per_day' => ['nullable', 'integer', 'min:1', 'max:4', 'required_with:nurse_visit_id'],
            
            // Duties & Babysitter specific
            'duration_hours' => ['nullable', 'integer', 'in:4,6,8,12,24', 'required_without:is_continuous_care'],
            'is_continuous_care' => ['nullable', 'boolean', 'required_without:duration_hours'],
            'is_day_shift' => ['nullable', 'boolean', 'required_without:nurse_visit_id'],
            
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

        return new CreateRequestDTO(
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            full_name: $this->buildFullName($data) ?? $data['full_name'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            problem_description: $data['problem_description'] ?? null,
            service_id: null, // Category 7 doesn't use service_id
            area_id: $data['area_id'] ?? null,
            category_id: 7, // Category 7: Duties
            name: $data['name'] ?? null,
            nurse_gender: $data['nurse_gender'] ?? null,
            time_type: null,
            scheduled_time: null,
            ending_time: null,
            location: $data['location'] ?? null,
            // Address fields
            use_saved_address: $this->normalizeBoolean($data['use_saved_address'] ?? false),
            address_city: $data['address_city'] ?? null,
            address_street: $data['address_street'] ?? null,
            address_building: $data['address_building'] ?? null,
            address_additional_information: $data['address_additional_information'] ?? null,
            // Category 2 fields (not used for Category 7)
            test_package_id: null,
            test_id: null,
            request_details_files: $requestDetailsPath ? [$requestDetailsPath] : null,
            notes: $data['notes'] ?? null,
            request_with_insurance: null,
            attach_front_face: null,
            attach_back_face: null,
            // Category 3 fields (not used for Category 7)
            ray_id: null,
            // Category 4 fields (not used for Category 7)
            machine_id: null,
            from_date: isset($data['from_date']) ? $data['from_date'] : null,
            to_date: isset($data['to_date']) ? $data['to_date'] : null,
            // Category 5 fields (not used for Category 7)
            physiotherapist_id: null,
            sessions_per_month: null,
            machines_included: null,
            physio_machines: null,
            // Category 7 specific fields
            nurse_visit_id: isset($data['nurse_visit_id']) ? (int)$data['nurse_visit_id'] : null,
            duty_id: isset($data['duty_id']) ? (int)$data['duty_id'] : null,
            babysitter_id: isset($data['babysitter_id']) ? (int)$data['babysitter_id'] : null,
            visits_per_day: isset($data['visits_per_day']) ? (int)$data['visits_per_day'] : null,
            duration_hours: isset($data['duration_hours']) ? (int)$data['duration_hours'] : null,
            is_continuous_care: $this->normalizeBoolean($data['is_continuous_care'] ?? false),
            is_day_shift: $this->normalizeBoolean($data['is_day_shift'] ?? true),
            additional_information: $data['additional_information'] ?? null,
        );
    }
}
