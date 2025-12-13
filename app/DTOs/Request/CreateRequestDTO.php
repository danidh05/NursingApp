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
        public array $service_ids = [],
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
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            full_name: $data['full_name'],
            phone_number: $data['phone_number'],
            problem_description: $data['problem_description'],
            service_ids: $data['service_ids'],
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
        );
    }
} 