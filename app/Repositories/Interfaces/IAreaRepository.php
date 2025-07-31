<?php

namespace App\Repositories\Interfaces;

use App\Models\Area;
use Illuminate\Database\Eloquent\Collection;

interface IAreaRepository
{
    public function getAll(): Collection;
    public function findById(int $id): ?Area;
    public function create(array $data): Area;
    public function update(Area $area, array $data): Area;
    public function delete(Area $area): bool;
    public function getWithUserCount(): Collection;
    public function getWithServicePriceCount(): Collection;
} 