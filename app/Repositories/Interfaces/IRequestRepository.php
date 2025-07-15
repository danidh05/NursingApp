<?php

namespace App\Repositories\Interfaces;

use App\Models\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface IRequestRepository
{
    public function create(array $data): Request;
    public function update(Request $request, array $data): Request;
    public function delete(Request $request): bool;
    public function find(int $id): ?Request;
    public function findOrFail(int $id): Request;
    public function getAll(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getAllByUser(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function attachServices(Request $request, array $serviceIds): void;
    public function updateServices(Request $request, array $serviceIds): void;
} 