<?php

namespace App\Repositories\Interfaces;

use App\DTOs\Request\CreateRequestDTO;
use App\DTOs\Request\UpdateRequestDTO;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface IRequestRepository
{
    public function create(CreateRequestDTO $dto, User $user): Request;
    public function update(int $id, UpdateRequestDTO $dto, User $user): Request;
    public function findById(int $id, User $user): Request;
    public function getAll(User $user): Collection;
    public function softDelete(int $id, User $user): void;
} 