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
        public ?int $area_id,
        public string $full_name,
        public string $phone_number,
        public ?string $name,
        public ?string $problem_description,
        public string $status,
        public ?int $time_needed_to_arrive,
        public ?string $nurse_gender,
        public ?string $time_type,
        public ?string $scheduled_time,
        public ?string $ending_time,
        public string $location,
        public ?float $latitude,      // This comes from user info, not database
        public ?float $longitude,     // This comes from user info, not database
        public ?float $total_price,
        public ?float $discount_percentage,
        public ?float $discounted_price,
        public ?int $thread_id,       // Chat thread ID for admin-user communication
        public ?Carbon $deleted_at,
        public Carbon $created_at,
        public Carbon $updated_at,
        public User $user,
        public ?array $area = null,
        public array $services = [],
    ) {}

    public static function fromModel(Request $request): self
    {
        // Get cached time_needed_to_arrive if available and calculate remaining time
        $timeNeededToArrive = null;
        $cacheKey = 'time_needed_to_arrive_' . $request->id;
        $cachedData = \Cache::get($cacheKey);
        
        if ($cachedData) {
            // Calculate elapsed time correctly - start time should be the first parameter
            $elapsedMinutes = $cachedData['start_time']->diffInMinutes(now());
            $timeNeededToArrive = max(0, $cachedData['time_needed'] - $elapsedMinutes);
        }
        
        return new self(
            id: $request->id,
            user_id: $request->user_id,
            area_id: $request->area_id,
            full_name: $request->full_name,
            phone_number: $request->phone_number,
            name: $request->name,
            problem_description: $request->problem_description,
            status: $request->status,
            time_needed_to_arrive: $timeNeededToArrive,
            nurse_gender: $request->nurse_gender,
            time_type: $request->time_type,
            scheduled_time: $request->scheduled_time,
            ending_time: $request->ending_time,
            location: $request->location,
            latitude: $request->user->latitude ?? null,    // Get from user info
            longitude: $request->user->longitude ?? null,  // Get from user info
            total_price: $request->total_price,
            discount_percentage: $request->discount_percentage,
            discounted_price: $request->discounted_price,
            thread_id: $request->chatThread?->id ?? null,  // Get chat thread ID if exists
            deleted_at: $request->deleted_at,
            created_at: $request->created_at,
            updated_at: $request->updated_at,
            user: $request->user,
            area: $request->area ? [
                'id' => $request->area->id,
                'name' => $request->area->name,
            ] : null,
            services: $request->services->toArray(),
        );
    }
} 