<?php

namespace App\Services\Interfaces;

use App\DTOs\Request\RequestResponseDTO;
use App\Models\User;

interface IRequestService
{
    public function createRequest(array $data, User $user): RequestResponseDTO;
    public function updateRequest(int $id, array $data, User $user): RequestResponseDTO;
    public function getRequest(int $id, User $user): RequestResponseDTO;
    public function getAllRequests(User $user, array $filters = []): array;
    public function softDeleteRequest(int $id, User $user): void;
} 