<?php

namespace App\DTOs\Request;

use App\Models\Request;
use App\Models\User;
use Carbon\Carbon;

class RequestResponseDTO
{
    public function __construct(
        public int $id,
        public int $user_id,
        public string $full_name,
        public string $phone_number,
        public ?string $problem_description,
        public string $status,
        public ?int $time_needed_to_arrive,
        public ?string $nurse_gender,
        public ?string $time_type,
        public ?string $scheduled_time,
        public string $location,
        public ?float $latitude,
        public ?float $longitude,
        public ?Carbon $deleted_at,
        public Carbon $created_at,
        public Carbon $updated_at,
        public User $user,
        public array $services = [],
    ) {}

    public static function fromModel(Request $request): self
    {
        // Get cached time_needed_to_arrive if available
        $timeNeededToArrive = $request->time_needed_to_arrive;
        $cacheKey = 'time_needed_to_arrive_' . $request->id;
        $cachedData = \Cache::get($cacheKey);
        
        if ($cachedData) {
            $elapsedMinutes = now()->diffInMinutes($cachedData['start_time']);
            $timeNeededToArrive = max(0, $cachedData['time_needed'] - $elapsedMinutes);
        } else {
            $timeNeededToArrive = null;
        }
        
        return new self(
            id: $request->id,
            user_id: $request->user_id,
            full_name: $request->full_name,
            phone_number: $request->phone_number,
            problem_description: $request->problem_description,
            status: $request->status,
            time_needed_to_arrive: $timeNeededToArrive,
            nurse_gender: $request->nurse_gender,
            time_type: $request->time_type,
            scheduled_time: $request->scheduled_time,
            location: $request->location,
            latitude: $request->latitude,
            longitude: $request->longitude,
            deleted_at: $request->deleted_at,
            created_at: $request->created_at,
            updated_at: $request->updated_at,
            user: $request->user,
            services: $request->services->toArray(),
        );
    }
} 