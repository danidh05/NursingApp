<?php

namespace App\Repositories;

use App\DTOs\Request\CreateRequestDTO;
use App\DTOs\Request\UpdateRequestDTO;
use App\Models\Request;
use App\Models\User;
use App\Repositories\Interfaces\IRequestRepository;
use Illuminate\Database\Eloquent\Collection;

class RequestRepository implements IRequestRepository
{
    public function create(CreateRequestDTO $dto, User $user, ?float $totalPrice = null): Request
    {
        // Build full_name from first_name and last_name if provided
        $fullName = $dto->full_name;
        if (!$fullName && ($dto->first_name || $dto->last_name)) {
            $parts = array_filter([$dto->first_name, $dto->last_name]);
            $fullName = !empty($parts) ? implode(' ', $parts) : null;
        }
        
        $categoryId = $dto->category_id ?? 1;
        
        // Base fields common to all categories
        $requestData = [
            'user_id' => $user->id,
            'category_id' => $categoryId,
            'first_name' => $dto->first_name,
            'last_name' => $dto->last_name,
            'full_name' => $fullName,
            'phone_number' => $dto->phone_number,
            'name' => $dto->name,
            'problem_description' => $dto->problem_description,
            'nurse_gender' => $dto->nurse_gender,
            'location' => $dto->location,
            'status' => Request::STATUS_SUBMITTED,
            // Address fields (common to all categories)
            'use_saved_address' => $dto->use_saved_address ?? false,
            'address_city' => $dto->address_city,
            'address_street' => $dto->address_street,
            'address_building' => $dto->address_building,
            'address_additional_information' => $dto->address_additional_information,
            'additional_information' => $dto->additional_information,
        ];
        
        // Category-specific fields - only include fields relevant to the category
        if ($categoryId === 1) {
            // Category 1: Service Request
            $requestData['area_id'] = $dto->area_id ?? $user->area_id;
            if ($dto->time_type !== null) {
                $requestData['time_type'] = $dto->time_type;
            }
            if ($dto->scheduled_time !== null) {
                $requestData['scheduled_time'] = $dto->scheduled_time;
            }
            if ($dto->ending_time !== null) {
                $requestData['ending_time'] = $dto->ending_time;
            }
            // Do NOT include Category 2 fields (test_package_id, test_id, etc.)
        } elseif ($categoryId === 2) {
            // Category 2: Tests
            if ($dto->test_package_id !== null) {
                $requestData['test_package_id'] = $dto->test_package_id;
            }
            if ($dto->test_id !== null) {
                $requestData['test_id'] = $dto->test_id;
            }
            if ($dto->request_details_files !== null) {
                // request_details_files is an array, encode as JSON for database storage
                $requestData['request_details_files'] = json_encode($dto->request_details_files);
            }
            if ($dto->notes !== null) {
                $requestData['notes'] = $dto->notes;
            }
            $requestData['request_with_insurance'] = $dto->request_with_insurance ?? false;
            if ($dto->attach_front_face !== null) {
                $requestData['attach_front_face'] = $dto->attach_front_face;
            }
            if ($dto->attach_back_face !== null) {
                $requestData['attach_back_face'] = $dto->attach_back_face;
            }
            // Do NOT include Category 1 fields (area_id, time_type, scheduled_time, ending_time)
        } elseif ($categoryId === 3) {
            // Category 3: Rays (with area-based pricing)
            $requestData['area_id'] = $dto->area_id ?? $user->area_id;
            if ($dto->ray_id !== null) {
                $requestData['ray_id'] = $dto->ray_id;
            }
            if ($dto->request_details_files !== null) {
                // request_details_files is an array, encode as JSON for database storage
                $requestData['request_details_files'] = json_encode($dto->request_details_files);
            }
            if ($dto->notes !== null) {
                $requestData['notes'] = $dto->notes;
            }
            // Do NOT include Category 1, 2, or 4 specific fields
        } elseif ($categoryId === 4) {
            // Category 4: Machines (with area-based pricing)
            $requestData['area_id'] = $dto->area_id ?? $user->area_id;
            if ($dto->machine_id !== null) {
                $requestData['machine_id'] = $dto->machine_id;
            }
            if ($dto->from_date !== null) {
                $requestData['from_date'] = $dto->from_date;
            }
            if ($dto->to_date !== null) {
                $requestData['to_date'] = $dto->to_date;
            }
            // Do NOT include Category 1, 2, 3, or 5 specific fields
        } elseif ($categoryId === 5) {
            // Category 5: Physiotherapists (with area-based pricing)
            $requestData['area_id'] = $dto->area_id ?? $user->area_id;
            if ($dto->physiotherapist_id !== null) {
                $requestData['physiotherapist_id'] = $dto->physiotherapist_id;
            }
            if ($dto->sessions_per_month !== null) {
                $requestData['sessions_per_month'] = $dto->sessions_per_month;
            }
            $requestData['machines_included'] = $dto->machines_included ?? false;
            if ($dto->physio_machines !== null) {
                // Fetch physio machine data and store as JSON
                $physioMachinesData = [];
                foreach ($dto->physio_machines as $machineId) {
                    $machine = \App\Models\PhysioMachine::find($machineId);
                    if ($machine) {
                        $physioMachinesData[] = [
                            'id' => $machine->id,
                            'name' => $machine->name,
                            'price' => $machine->price,
                        ];
                    }
                }
                $requestData['physio_machines'] = !empty($physioMachinesData) ? json_encode($physioMachinesData) : null;
            }
            if ($dto->from_date !== null) {
                $requestData['from_date'] = $dto->from_date;
            }
            if ($dto->to_date !== null) {
                $requestData['to_date'] = $dto->to_date;
            }
            if ($dto->request_details_files !== null && !empty($dto->request_details_files)) {
                // request_details is a single PDF file, store as JSON array
                $requestData['request_details_files'] = json_encode($dto->request_details_files);
            }
            if ($dto->notes !== null) {
                $requestData['notes'] = $dto->notes;
            }
            // Do NOT include Category 1, 2, 3, or 4 specific fields
        } elseif ($categoryId === 7) {
            // Category 7: Duties (with area-based pricing)
            $requestData['area_id'] = $dto->area_id ?? $user->area_id;
            if ($dto->nurse_visit_id !== null) {
                $requestData['nurse_visit_id'] = $dto->nurse_visit_id;
            }
            if ($dto->duty_id !== null) {
                $requestData['duty_id'] = $dto->duty_id;
            }
            if ($dto->babysitter_id !== null) {
                $requestData['babysitter_id'] = $dto->babysitter_id;
            }
            if ($dto->visits_per_day !== null) {
                $requestData['visits_per_day'] = $dto->visits_per_day;
            }
            if ($dto->duration_hours !== null) {
                $requestData['duration_hours'] = $dto->duration_hours;
            }
            $requestData['is_continuous_care'] = $dto->is_continuous_care ?? false;
            $requestData['is_day_shift'] = $dto->is_day_shift ?? true;
            if ($dto->from_date !== null) {
                $requestData['from_date'] = $dto->from_date;
            }
            if ($dto->to_date !== null) {
                $requestData['to_date'] = $dto->to_date;
            }
            if ($dto->request_details_files !== null && !empty($dto->request_details_files)) {
                // request_details is a single PDF file, store as JSON array
                $requestData['request_details_files'] = json_encode($dto->request_details_files);
            }
            if ($dto->notes !== null) {
                $requestData['notes'] = $dto->notes;
            }
            // Do NOT include Category 1, 2, 3, 4, or 5 specific fields
        } else {
            // Future categories (6-8): Only common fields, no category-specific fields yet
            // Can be extended when implementing other categories
        }
        
        $request = Request::create($requestData);

        // Attach service (only for Category 1: Service Request)
        if ($categoryId === 1 && $dto->service_id) {
            $request->services()->attach($dto->service_id);
        }

        // Load relationships based on category
        $relationships = ['user', 'area', 'chatThread', 'nurse', 'category'];
        if ($categoryId === 1) {
            $relationships[] = 'services';
        } elseif ($categoryId === 2) {
            $relationships[] = 'testPackage';
            $relationships[] = 'test';
        } elseif ($categoryId === 3) {
            $relationships[] = 'ray';
        } elseif ($categoryId === 4) {
            $relationships[] = 'machine';
        } elseif ($categoryId === 5) {
            $relationships[] = 'physiotherapist';
        } elseif ($categoryId === 7) {
            $relationships[] = 'nurseVisit';
            $relationships[] = 'duty';
            $relationships[] = 'babysitter';
        }

        return $request->load($relationships);
    }

    public function update(int $id, UpdateRequestDTO $dto, User $user): Request
    {
        // Remove any manual transaction management
        // For updates, admins should be able to update any request
        if ($user->role->name === 'admin') {
            $request = Request::with(['services', 'user.role', 'nurse', 'category', 'testPackage', 'test', 'ray', 'machine', 'physiotherapist', 'nurseVisit', 'duty', 'babysitter'])->whereNull('deleted_at')->findOrFail($id);
        } else {
            $request = $this->findById($id, $user);
        }
        
        $updateData = array_filter([
            'full_name' => $dto->full_name,
            'phone_number' => $dto->phone_number,
            'name' => $dto->name,
            'problem_description' => $dto->problem_description,
            'status' => $dto->status,
            'time_needed_to_arrive' => $dto->time_needed_to_arrive,
            'nurse_gender' => $dto->nurse_gender,
            'time_type' => $dto->time_type,
            'scheduled_time' => $dto->scheduled_time,
        ], fn($value) => $value !== null);

        // Handle nurse_id separately to allow null values
        if (array_key_exists('nurse_id', $dto->toArray())) {
            $updateData['nurse_id'] = $dto->nurse_id;
        }

        $request->update($updateData);

        return $request->load('services', 'user', 'area', 'chatThread', 'nurse', 'category', 'testPackage', 'test', 'ray', 'machine', 'physiotherapist', 'nurseVisit', 'duty', 'babysitter');
    }

    public function findById(int $id, User $user): Request
    {
        $query = Request::with(['services', 'user.role', 'area', 'chatThread', 'nurse', 'category', 'testPackage', 'test', 'ray', 'machine', 'physiotherapist']);
        // Ensure user role is loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }
        if ($user->role->name === 'admin') {
            $request = $query->whereNull('deleted_at')->findOrFail($id);
        } else {
            $request = $query->withTrashed()->where('user_id', $user->id)->findOrFail($id);
        }
        return $request;
    }

    public function getAll(User $user): Collection
    {
        $query = Request::with(['services', 'user.role', 'area', 'chatThread', 'nurse', 'category', 'testPackage', 'test', 'ray', 'machine', 'physiotherapist']);

        // Ensure user role is loaded
        if (!$user->relationLoaded('role')) {
            $user->load('role');
        }

        if ($user->role->name === 'admin') {
            // Admin sees all non-deleted requests
            return $query->whereNull('deleted_at')->get();
        } else {
            // User sees only their own requests (including soft deleted)
            return $query->where('user_id', $user->id)->get();
        }
    }

    public function softDelete(int $id, User $user): void
    {
        // Remove any manual transaction management
        $request = $this->findById($id, $user);
        
        if ($user->role->name === 'admin') {
            // Only admin can soft delete
            $request->delete();
        }
    }
} 