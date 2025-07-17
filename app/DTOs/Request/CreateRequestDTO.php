<?php

namespace App\DTOs\Request;

class CreateRequestDTO
{
    public function __construct(
        public string $full_name,
        public string $phone_number,
        public string $problem_description,
        public array $service_ids,
        public ?string $nurse_gender = null,
        public ?string $time_type = null,
        public ?string $scheduled_time = null,
        public string $location,
        public ?float $latitude = null,
        public ?float $longitude = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            full_name: $data['full_name'],
            phone_number: $data['phone_number'],
            problem_description: $data['problem_description'],
            service_ids: $data['service_ids'],
            nurse_gender: $data['nurse_gender'] ?? null,
            time_type: $data['time_type'] ?? null,
            scheduled_time: $data['scheduled_time'] ?? null,
            location: $data['location'],
            latitude: $data['latitude'] ?? null,
            longitude: $data['longitude'] ?? null,
        );
    }
} 