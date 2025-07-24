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
    public function create(CreateRequestDTO $dto, User $user): Request
    {
        // Remove any manual transaction management - let Laravel handle it
        $request = Request::create([
            'user_id' => $user->id,
            'full_name' => $dto->full_name,
            'phone_number' => $dto->phone_number,
            'name' => $dto->name,
            'problem_description' => $dto->problem_description,
            'nurse_gender' => $dto->nurse_gender,
            'time_type' => $dto->time_type,
            'scheduled_time' => $dto->scheduled_time,
            'ending_time' => $dto->ending_time,
            'location' => $dto->location,
            'status' => Request::STATUS_SUBMITTED, // Use new status constant
        ]);

        // Attach services
        if (!empty($dto->service_ids)) {
            $request->services()->attach($dto->service_ids);
        }

        return $request->load('services', 'user');
    }

    public function update(int $id, UpdateRequestDTO $dto, User $user): Request
    {
        // Remove any manual transaction management
        // For updates, admins should be able to update any request
        if ($user->role->name === 'admin') {
            $request = Request::with(['services', 'user.role'])->whereNull('deleted_at')->findOrFail($id);
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

        $request->update($updateData);

        return $request->load('services', 'user');
    }

    public function findById(int $id, User $user): Request
    {
        $query = Request::with(['services', 'user.role']);
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
        $query = Request::with(['services', 'user.role']);

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