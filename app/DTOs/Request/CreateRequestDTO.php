<?php

namespace App\DTOs\Request;

use Illuminate\Support\Carbon;

class CreateRequestDTO
{
    public function __construct(
        public readonly string $full_name,
        public readonly string $phone_number,
        public readonly string $location,
        public readonly string $time_type,
        public readonly ?string $nurse_gender,
        public readonly array $service_ids,
        public readonly ?string $problem_description,
        public readonly Carbon $scheduled_time,
        public readonly ?Carbon $ending_time = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            full_name: $data['full_name'],
            phone_number: $data['phone_number'],
            location: $data['location'],
            time_type: $data['time_type'],
            nurse_gender: $data['nurse_gender'] ?? null,
            service_ids: $data['service_ids'],
            problem_description: $data['problem_description'] ?? null,
            scheduled_time: Carbon::parse($data['scheduled_time']),
            ending_time: isset($data['ending_time']) ? Carbon::parse($data['ending_time']) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'location' => $this->location,
            'time_type' => $this->time_type,
            'nurse_gender' => $this->nurse_gender,
            'service_ids' => $this->service_ids,
            'problem_description' => $this->problem_description,
            'scheduled_time' => $this->scheduled_time->toDateTimeString(),
            'ending_time' => $this->ending_time?->toDateTimeString(),
        ];
    }
} 