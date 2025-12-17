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
        public int $category_id,      // Category ID (1=Service Request, 2=Tests, etc.)
        public ?int $area_id,
        public ?string $first_name,
        public ?string $last_name,
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
        public ?string $location,     // Can be null for some categories
        public ?float $latitude,      // This comes from user info, not database
        public ?float $longitude,     // This comes from user info, not database
        public ?float $total_price,
        public ?float $discount_percentage,
        public ?float $discounted_price,
        public ?int $thread_id,       // Chat thread ID for admin-user communication
        // Address fields
        public ?bool $use_saved_address,
        public ?string $address_city,
        public ?string $address_street,
        public ?string $address_building,
        public ?string $address_additional_information,
        public ?string $additional_information,  // Common field for all categories
        // Category 2: Tests specific fields
        public ?int $test_package_id,
        public ?int $test_id,
        public ?array $request_details_files,  // Array of file URLs
        public ?string $notes,
        public ?bool $request_with_insurance,
        public ?string $attach_front_face,  // File URL
        public ?string $attach_back_face,   // File URL
        public ?Carbon $deleted_at,
        public Carbon $created_at,
        public Carbon $updated_at,
        public User $user,
        public ?array $area = null,
        public ?array $nurse = null,  // Nurse information including name
        public array $services = [],
        public ?array $test_package = null,  // Test package information (Category 2)
        public ?array $test = null,          // Test information (Category 2)
        // Category 3: Rays specific fields
        public ?int $ray_id = null,
        public ?array $ray = null,          // Ray information (Category 3)
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
        
        // Convert file paths to full URLs
        $requestDetailsFiles = null;
        // request_details_files is cast to 'array' in the model, so it's already decoded
        $files = $request->request_details_files;
        
        // Handle both array (from cast) and JSON string (fallback)
        if (is_string($files)) {
            $files = json_decode($files, true);
        }
        
        if ($files && is_array($files) && !empty($files)) {
            // Filter out non-string values and convert to URLs
            $requestDetailsFiles = array_filter(
                array_map(function ($filePath) {
                    // Only process string file paths
                    if (is_string($filePath) && !empty($filePath)) {
                        return \Illuminate\Support\Facades\Storage::disk('public')->url($filePath);
                    }
                    return null;
                }, $files),
                fn($value) => $value !== null
            );
            // Re-index array after filtering
            $requestDetailsFiles = array_values($requestDetailsFiles);
        }
        
        // attach_front_face and attach_back_face are strings, check if not empty
        $attachFrontFace = (!empty($request->attach_front_face) && is_string($request->attach_front_face))
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($request->attach_front_face)
            : null;
            
        $attachBackFace = (!empty($request->attach_back_face) && is_string($request->attach_back_face))
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($request->attach_back_face)
            : null;
        
        return new self(
            id: $request->id,
            user_id: $request->user_id,
            category_id: $request->category_id ?? 1,
            area_id: $request->area_id,
            first_name: $request->first_name,
            last_name: $request->last_name,
            full_name: $request->full_name,
            phone_number: $request->phone_number,
            name: $request->name,
            problem_description: $request->problem_description,
            status: $request->status,
            time_needed_to_arrive: $timeNeededToArrive,
            nurse_gender: $request->nurse_gender,
            time_type: $request->time_type,
            scheduled_time: $request->scheduled_time?->toIso8601String(),
            ending_time: $request->ending_time?->toIso8601String(),
            location: $request->location,
            latitude: $request->user->latitude ?? null,    // Get from user info
            longitude: $request->user->longitude ?? null,  // Get from user info
            total_price: $request->total_price,
            discount_percentage: $request->discount_percentage,
            discounted_price: $request->discounted_price,
            thread_id: $request->chatThread?->id ?? null,  // Get chat thread ID if exists
            // Address fields
            use_saved_address: $request->use_saved_address ?? false,
            address_city: $request->address_city,
            address_street: $request->address_street,
            address_building: $request->address_building,
            address_additional_information: $request->address_additional_information,
            additional_information: $request->additional_information,
            // Category 2: Tests specific fields
            test_package_id: $request->test_package_id,
            test_id: $request->test_id,
            request_details_files: $requestDetailsFiles,
            notes: $request->notes,
            request_with_insurance: $request->request_with_insurance ?? false,
            attach_front_face: $attachFrontFace,
            attach_back_face: $attachBackFace,
            deleted_at: $request->deleted_at,
            created_at: $request->created_at,
            updated_at: $request->updated_at,
            user: $request->user,
            area: $request->area ? [
                'id' => $request->area->id,
                'name' => $request->area->name,
            ] : null,
            nurse: $request->nurse ? [
                'id' => $request->nurse->id,
                'name' => $request->nurse->name,
                'phone_number' => $request->nurse->phone_number,
                'gender' => $request->nurse->gender,
                'profile_picture' => $request->nurse->profile_picture,
            ] : null,
            services: $request->services->toArray(),
            test_package: $request->testPackage ? [
                'id' => $request->testPackage->id,
                'name' => $request->testPackage->name,
                'results' => $request->testPackage->results,
                'price' => $request->testPackage->price,
                'image' => $request->testPackage->image_url,
                'show_details' => $request->testPackage->show_details,
            ] : null,
            test: $request->test ? [
                'id' => $request->test->id,
                'sample_type' => $request->test->sample_type,
                'price' => $request->test->price,
                'image' => $request->test->image_url,
            ] : null,
            // Category 3: Rays specific fields
            ray_id: $request->ray_id,
            ray: $request->ray ? [
                'id' => $request->ray->id,
                'name' => $request->ray->name,
                'price' => $request->ray->price,
                'image' => $request->ray->image_url,
            ] : null,
        );
    }
} 