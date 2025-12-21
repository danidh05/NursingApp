<?php

namespace App\DTOs\Request;

use App\Models\Request;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

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
        // Category 4: Machines specific fields
        public ?int $machine_id = null,     // Machine ID (Category 4)
        public ?array $machine = null,      // Machine information (Category 4)
        public ?string $from_date = null,   // Category 4: rental start date, Category 5: physiotherapist start date
        public ?string $to_date = null,     // Category 4: rental end date, Category 5: physiotherapist end date
        // Category 5: Physiotherapists specific fields
        public ?int $physiotherapist_id = null,     // Physiotherapist ID (Category 5)
        public ?array $physiotherapist = null,      // Physiotherapist information (Category 5)
        public ?int $sessions_per_month = null,     // Category 5: number of sessions per month
        public ?bool $machines_included = false,    // Category 5: whether machines are included
        public ?array $physio_machines = null,       // Category 5: array of physio machine data
        // Category 7: Duties specific fields
        public ?int $nurse_visit_id = null,          // Category 7: Nurse Visits subcategory
        public ?int $duty_id = null,                 // Category 7: Duties subcategory
        public ?int $babysitter_id = null,           // Category 7: Babysitter subcategory
        public ?int $visits_per_day = null,          // Category 7: Nurse Visits - visits per day (1-4)
        public ?int $duration_hours = null,          // Category 7: Duties/Babysitter - duration in hours
        public ?bool $is_continuous_care = false,    // Category 7: Duties - continuous care
        public ?bool $is_day_shift = true,           // Category 7: Duties/Babysitter - day shift or night shift
        public ?array $nurse_visit = null,           // Category 7: Nurse Visit information
        public ?array $duty = null,                  // Category 7: Duty information
        public ?array $babysitter = null,            // Category 7: Babysitter information
    ) {}

    public static function fromModel(Request $request): self
    {
        // Get cached time_needed_to_arrive if available and calculate remaining time
        $timeNeededToArrive = null;
        $cacheKey = 'time_needed_to_arrive_' . $request->id;
        $cachedData = Cache::get($cacheKey);
        
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
                        return Storage::disk('public')->url($filePath);
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
            ? Storage::disk('public')->url($request->attach_front_face)
            : null;
            
        $attachBackFace = (!empty($request->attach_back_face) && is_string($request->attach_back_face))
            ? Storage::disk('public')->url($request->attach_back_face)
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
            ray: $request->ray ? (function() use ($request) {
                $locale = app()->getLocale() ?: 'en';
                $translation = $request->ray->translate($locale);
                return [
                    'id' => $request->ray->id,
                    'name' => $translation ? $translation->name : $request->ray->name,
                    'price' => $request->ray->price,
                    'image' => $request->ray->image_url,
                    'about_ray' => $translation?->about_ray,
                    'instructions' => $translation?->instructions,
                    'additional_information' => $translation?->additional_information,
                ];
            })() : null,
            // Category 4: Machines specific fields
            machine_id: $request->machine_id,
            from_date: $request->from_date ? (is_string($request->from_date) ? $request->from_date : $request->from_date->format('Y-m-d')) : null,
            to_date: $request->to_date ? (is_string($request->to_date) ? $request->to_date : $request->to_date->format('Y-m-d')) : null,
            machine: $request->machine ? (function() use ($request) {
                $locale = app()->getLocale() ?: 'en';
                $translation = $request->machine->translate($locale);
                return [
                    'id' => $request->machine->id,
                    'name' => $translation ? $translation->name : $request->machine->name,
                    'price' => $request->machine->price,
                    'image' => $request->machine->image_url,
                    'description' => $translation?->description,
                    'additional_information' => $translation?->additional_information,
                ];
            })() : null,
            // Category 5: Physiotherapists specific fields
            physiotherapist_id: $request->physiotherapist_id,
            sessions_per_month: $request->sessions_per_month,
            machines_included: $request->machines_included ?? false,
            physio_machines: $request->physio_machines ? (function() use ($request) {
                // Return the physio_machines array as stored (should already contain machine data)
                return is_array($request->physio_machines) ? $request->physio_machines : json_decode($request->physio_machines, true);
            })() : null,
            physiotherapist: $request->physiotherapist ? (function() use ($request) {
                $locale = app()->getLocale() ?: 'en';
                $translation = $request->physiotherapist->translate($locale);
                return [
                    'id' => $request->physiotherapist->id,
                    'name' => $translation ? $translation->name : $request->physiotherapist->name,
                    'price' => $request->physiotherapist->price,
                    'image' => $request->physiotherapist->image_url,
                    'job_name' => $request->physiotherapist->job_name,
                    'job_specification' => $request->physiotherapist->job_specification,
                    'specialization' => $request->physiotherapist->specialization,
                    'years_of_experience' => $request->physiotherapist->years_of_experience,
                    'description' => $translation?->description,
                ];
            })() : null,
            // Category 7: Duties specific fields
            nurse_visit_id: $request->nurse_visit_id,
            duty_id: $request->duty_id,
            babysitter_id: $request->babysitter_id,
            visits_per_day: $request->visits_per_day,
            duration_hours: $request->duration_hours,
            is_continuous_care: $request->is_continuous_care ?? false,
            is_day_shift: $request->is_day_shift ?? true,
            nurse_visit: $request->nurseVisit ? (function() use ($request) {
                $locale = app()->getLocale() ?: 'en';
                $translation = $request->nurseVisit->translate($locale);
                return [
                    'id' => $request->nurseVisit->id,
                    'name' => $request->nurseVisit->name,
                    'image' => $request->nurseVisit->image_url,
                    'price_per_1_visit' => $request->nurseVisit->price_per_1_visit,
                    'price_per_2_visits' => $request->nurseVisit->price_per_2_visits,
                    'price_per_3_visits' => $request->nurseVisit->price_per_3_visits,
                    'price_per_4_visits' => $request->nurseVisit->price_per_4_visits,
                    'about' => $translation?->about,
                    'terms_and_conditions' => $translation?->terms_and_conditions,
                    'additional_instructions' => $translation?->additional_instructions,
                    'service_include' => $translation?->service_include,
                    'description' => $translation?->description,
                    'additional_information' => $translation?->additional_information,
                ];
            })() : null,
            duty: $request->duty ? (function() use ($request) {
                $locale = app()->getLocale() ?: 'en';
                $translation = $request->duty->translate($locale);
                return [
                    'id' => $request->duty->id,
                    'name' => $request->duty->name,
                    'image' => $request->duty->image_url,
                    'day_shift_price_4_hours' => $request->duty->day_shift_price_4_hours,
                    'day_shift_price_6_hours' => $request->duty->day_shift_price_6_hours,
                    'day_shift_price_8_hours' => $request->duty->day_shift_price_8_hours,
                    'day_shift_price_12_hours' => $request->duty->day_shift_price_12_hours,
                    'night_shift_price_4_hours' => $request->duty->night_shift_price_4_hours,
                    'night_shift_price_6_hours' => $request->duty->night_shift_price_6_hours,
                    'night_shift_price_8_hours' => $request->duty->night_shift_price_8_hours,
                    'night_shift_price_12_hours' => $request->duty->night_shift_price_12_hours,
                    'continuous_care_price' => $request->duty->continuous_care_price,
                    'about' => $translation?->about,
                    'terms_and_conditions' => $translation?->terms_and_conditions,
                    'additional_instructions' => $translation?->additional_instructions,
                    'service_include' => $translation?->service_include,
                    'description' => $translation?->description,
                    'additional_information' => $translation?->additional_information,
                ];
            })() : null,
            babysitter: $request->babysitter ? (function() use ($request) {
                $locale = app()->getLocale() ?: 'en';
                $translation = $request->babysitter->translate($locale);
                return [
                    'id' => $request->babysitter->id,
                    'name' => $request->babysitter->name,
                    'image' => $request->babysitter->image_url,
                    'day_shift_price_12_hours' => $request->babysitter->day_shift_price_12_hours,
                    'day_shift_price_24_hours' => $request->babysitter->day_shift_price_24_hours,
                    'night_shift_price_12_hours' => $request->babysitter->night_shift_price_12_hours,
                    'night_shift_price_24_hours' => $request->babysitter->night_shift_price_24_hours,
                    'about' => $translation?->about,
                    'terms_and_conditions' => $translation?->terms_and_conditions,
                    'additional_instructions' => $translation?->additional_instructions,
                    'service_include' => $translation?->service_include,
                    'description' => $translation?->description,
                    'additional_information' => $translation?->additional_information,
                ];
            })() : null,
        );
    }
} 