<?php

namespace App\DTOs\Request;

class UpdateRequestDTO
{
    public function __construct(
        public ?string $full_name = null,
        public ?string $phone_number = null,
        public ?string $name = null,                // Optional request name/title
        public ?string $problem_description = null,
        public ?string $status = null,
        public ?int $time_needed_to_arrive = null,
        public ?string $nurse_gender = null,
        public ?string $time_type = null,
        public ?string $scheduled_time = null,
        public ?int $nurse_id = null,               // Nurse assignment
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            full_name: $data['full_name'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            name: $data['name'] ?? null,
            problem_description: $data['problem_description'] ?? null,
            status: $data['status'] ?? null,
            time_needed_to_arrive: $data['time_needed_to_arrive'] ?? null,
            nurse_gender: $data['nurse_gender'] ?? null,
            time_type: $data['time_type'] ?? null,
            scheduled_time: $data['scheduled_time'] ?? null,
            nurse_id: $data['nurse_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'name' => $this->name,
            'problem_description' => $this->problem_description,
            'status' => $this->status,
            'time_needed_to_arrive' => $this->time_needed_to_arrive,
            'nurse_gender' => $this->nurse_gender,
            'time_type' => $this->time_type,
            'scheduled_time' => $this->scheduled_time,
            'nurse_id' => $this->nurse_id,
        ];
    }
} 