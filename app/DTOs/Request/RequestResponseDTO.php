<?php

namespace App\DTOs\Request;

use App\Models\Request;
use Illuminate\Support\Carbon;

class RequestResponseDTO
{
    public function __construct(
        public readonly int $id,
        public readonly int $user_id,
        public readonly string $full_name,
        public readonly string $phone_number,
        public readonly string $location,
        public readonly string $time_type,
        public readonly ?string $nurse_gender,
        public readonly array $services,
        public readonly ?string $problem_description,
        public readonly Carbon $scheduled_time,
        public readonly ?Carbon $ending_time,
        public readonly string $status,
        public readonly ?int $nurse_id,
        public readonly Carbon $created_at,
        public readonly Carbon $updated_at,
        public readonly ?Carbon $deleted_at,
    ) {}

    public static function fromModel(Request $request): self
    {
        return new self(
            id: $request->id,
            user_id: $request->user_id,
            full_name: $request->full_name,
            phone_number: $request->phone_number,
            location: $request->location,
            time_type: $request->time_type,
            nurse_gender: $request->nurse_gender,
            services: $request->services->map(fn($service) => [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'price' => $service->price,
            ])->toArray(),
            problem_description: $request->problem_description,
            scheduled_time: $request->scheduled_time,
            ending_time: $request->ending_time,
            status: $request->status,
            nurse_id: $request->nurse_id,
            created_at: $request->created_at,
            updated_at: $request->updated_at,
            deleted_at: $request->deleted_at,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'full_name' => $this->full_name,
            'phone_number' => $this->phone_number,
            'location' => $this->location,
            'time_type' => $this->time_type,
            'nurse_gender' => $this->nurse_gender,
            'services' => $this->services,
            'problem_description' => $this->problem_description,
            'scheduled_time' => $this->scheduled_time->toDateTimeString(),
            'ending_time' => $this->ending_time?->toDateTimeString(),
            'status' => $this->status,
            'nurse_id' => $this->nurse_id,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'deleted_at' => $this->deleted_at?->toDateTimeString(),
        ];
    }
} 