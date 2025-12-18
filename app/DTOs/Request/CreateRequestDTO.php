<?php

namespace App\DTOs\Request;

class CreateRequestDTO
{
    public function __construct(
        public ?string $first_name = null,
        public ?string $last_name = null,
        public ?string $full_name = null,
        public ?string $phone_number = null,
        public ?string $problem_description = null,
        public ?int $service_id = null,  // Only for Category 1: Service Request
        public ?int $area_id = null,
        public ?int $category_id = 1,            // Default to Category 1: Service Request
        public ?string $name = null,             // Optional request name/title
        public ?string $nurse_gender = null,
        public ?string $time_type = null,
        public ?string $scheduled_time = null,
        public ?string $ending_time = null,
        public ?string $location = null,         // Can be coordinates or address string
        // Address fields
        public ?bool $use_saved_address = false,
        public ?string $address_city = null,
        public ?string $address_street = null,
        public ?string $address_building = null,
        public ?string $address_additional_information = null,
        // Category 2: Tests specific fields
        public ?int $test_package_id = null,
        public ?int $test_id = null, // For individual test requests
        public ?array $request_details_files = null, // Array of file paths
        public ?string $notes = null,
        public ?bool $request_with_insurance = false,
        public ?string $attach_front_face = null, // File path
        public ?string $attach_back_face = null, // File path
        // Category 3: Rays specific fields
        public ?int $ray_id = null,
        // Category 4: Machines specific fields
        public ?int $machine_id = null,
        public ?string $from_date = null, // Category 4 only: rental start date
        public ?string $to_date = null, // Category 4 only: rental end date
        // Common field for all categories
        public ?string $additional_information = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            full_name: $data['full_name'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            problem_description: $data['problem_description'] ?? null,
            service_id: $data['service_id'] ?? null,  // Only for Category 1
            area_id: $data['area_id'] ?? null,
            category_id: $data['category_id'] ?? 1, // Default to Category 1: Service Request
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
            // Category 2 fields
            test_package_id: $data['test_package_id'] ?? null,
            test_id: $data['test_id'] ?? null,
            request_details_files: $data['request_details_files'] ?? null,
            notes: $data['notes'] ?? null,
            request_with_insurance: $data['request_with_insurance'] ?? false,
            attach_front_face: $data['attach_front_face'] ?? null,
            attach_back_face: $data['attach_back_face'] ?? null,
            // Category 3 fields
            ray_id: $data['ray_id'] ?? null,
            // Category 4 fields
            machine_id: $data['machine_id'] ?? null,
            from_date: $data['from_date'] ?? null,
            to_date: $data['to_date'] ?? null,
            additional_information: $data['additional_information'] ?? null,
        );
    }
} 