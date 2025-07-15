<?php

namespace App\DTOs\Request;

use Illuminate\Support\Carbon;

class UpdateRequestDTO
{
    public function __construct(
        public readonly ?string $full_name = null,
        public readonly ?string $phone_number = null,
        public readonly ?string $location = null,
        public readonly ?string $time_type = null,
        public readonly ?string $nurse_gender = null,
        public readonly ?array $service_ids = null,
        public readonly ?string $problem_description = null,
        public readonly ?Carbon $scheduled_time = null,
        public readonly ?Carbon $ending_time = null,
        public readonly ?string $status = null,
        public readonly ?int $nurse_id = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            full_name: $data['full_name'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            location: $data['location'] ?? null,
            time_type: $data['time_type'] ?? null,
            nurse_gender: $data['nurse_gender'] ?? null,
            service_ids: $data['service_ids'] ?? null,
            problem_description: $data['problem_description'] ?? null,
            scheduled_time: isset($data['scheduled_time']) ? Carbon::parse($data['scheduled_time']) : null,
            ending_time: isset($data['ending_time']) ? Carbon::parse($data['ending_time']) : null,
            status: $data['status'] ?? null,
            nurse_id: $data['nurse_id'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'location' => $this->location,
            'time_type' => $this->time_type,
            'nurse_gender' => $this->nurse_gender,
            'service_ids' => $this->service_ids,
            'problem_description' => $this->problem_description,
            'scheduled_time' => $this->scheduled_time?->toDateTimeString(),
            'ending_time' => $this->ending_time?->toDateTimeString(),
            'status' => $this->status,
            'nurse_id' => $this->nurse_id,
        ], fn($value) => $value !== null);
    }
} 