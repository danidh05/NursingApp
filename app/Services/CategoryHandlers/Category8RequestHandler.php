<?php

namespace App\Services\CategoryHandlers;

use App\DTOs\Request\CreateRequestDTO;

class Category8RequestHandler extends BaseCategoryRequestHandler
{
    public function getValidationRules(): array
    {
        return array_merge($this->getCommonRules(), [
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'slot_id' => ['required', 'integer', 'exists:doctor_availabilities,id'],
            'appointment_type' => ['required', 'string', 'in:check_at_home,check_at_clinic,video_call'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'request_details_files' => ['nullable', 'array'],
            'request_details_files.*' => ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    public function mapToDTO(array $data): CreateRequestDTO
    {
        $files = null;
        if (isset($data['request_details_files'])) {
            if (is_array($data['request_details_files'])) {
                $files = array_filter($data['request_details_files'], 'is_string');
                $files = !empty($files) ? array_values($files) : null;
            } elseif (is_string($data['request_details_files'])) {
                $decoded = json_decode($data['request_details_files'], true);
                if (is_array($decoded)) {
                    $files = array_filter($decoded, 'is_string');
                    $files = !empty($files) ? array_values($files) : null;
                }
            }
        }

        return new CreateRequestDTO(
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            full_name: $this->buildFullName($data) ?? $data['full_name'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            problem_description: $data['problem_description'] ?? null,
            service_id: null,
            area_id: $data['area_id'] ?? null,
            category_id: 8,
            name: $data['name'] ?? null,
            nurse_gender: $data['nurse_gender'] ?? null,
            time_type: null,
            scheduled_time: null,
            ending_time: null,
            location: $data['location'] ?? null,
            use_saved_address: $this->normalizeBoolean($data['use_saved_address'] ?? false),
            address_city: $data['address_city'] ?? null,
            address_street: $data['address_street'] ?? null,
            address_building: $data['address_building'] ?? null,
            address_additional_information: $data['address_additional_information'] ?? null,
            test_package_id: null,
            test_id: null,
            request_details_files: $files,
            notes: $data['notes'] ?? null,
            request_with_insurance: null,
            attach_front_face: null,
            attach_back_face: null,
            ray_id: null,
            machine_id: null,
            from_date: null,
            to_date: null,
            physiotherapist_id: null,
            sessions_per_month: null,
            machines_included: null,
            physio_machines: null,
            nurse_visit_id: null,
            duty_id: null,
            babysitter_id: null,
            visits_per_day: null,
            duration_hours: null,
            is_continuous_care: null,
            is_day_shift: null,
            doctor_id: $data['doctor_id'] ?? null,
            appointment_type: $data['appointment_type'] ?? null,
            slot_id: $data['slot_id'] ?? null,
            additional_information: $data['additional_information'] ?? null,
        );
    }
}



