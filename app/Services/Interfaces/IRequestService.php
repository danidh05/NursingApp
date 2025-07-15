<?php

namespace App\Services\Interfaces;

use App\DTOs\Request\CreateRequestDTO;
use App\DTOs\Request\RequestResponseDTO;
use App\DTOs\Request\UpdateRequestDTO;
use Illuminate\Pagination\LengthAwarePaginator;

interface IRequestService
{
    public function createRequest(CreateRequestDTO $dto, int $userId): RequestResponseDTO;
    public function updateRequest(int $requestId, UpdateRequestDTO $dto): RequestResponseDTO;
    public function deleteRequest(int $requestId): bool;
    public function getRequest(int $requestId): RequestResponseDTO;
    public function getAllRequests(array $filters = [], int $perPage = 15): LengthAwarePaginator;
    public function getUserRequests(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator;
} 