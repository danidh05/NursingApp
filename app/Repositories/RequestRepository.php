<?php

namespace App\Repositories;

use App\Models\Request;
use App\Repositories\Interfaces\IRequestRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class RequestRepository implements IRequestRepository
{
    public function create(array $data): Request
    {
        $request = Request::create($data);
        
        if (isset($data['service_ids'])) {
            $this->attachServices($request, $data['service_ids']);
        }

        return $request->load('services');
    }

    public function update(Request $request, array $data): Request
    {
        $request->update($data);

        if (isset($data['service_ids'])) {
            $this->updateServices($request, $data['service_ids']);
        }

        return $request->load('services');
    }

    public function delete(Request $request): bool
    {
        return $request->delete();
    }

    public function find(int $id): ?Request
    {
        return Request::with(['services', 'user'])->find($id);
    }

    public function findOrFail(int $id): Request
    {
        return Request::with(['services', 'user'])->findOrFail($id);
    }

    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Request::with(['services', 'user']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['time_type'])) {
            $query->where('time_type', $filters['time_type']);
        }

        if (isset($filters['nurse_gender'])) {
            $query->where('nurse_gender', $filters['nurse_gender']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function getAllByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Request::withTrashed()->with(['services'])
            ->where('user_id', $userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['time_type'])) {
            $query->where('time_type', $filters['time_type']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function attachServices(Request $request, array $serviceIds): void
    {
        $request->services()->attach($serviceIds);
    }

    public function updateServices(Request $request, array $serviceIds): void
    {
        $request->services()->sync($serviceIds);
    }
} 