<?php

namespace App\Repositories;

use App\Models\Area;
use App\Repositories\Interfaces\IAreaRepository;
use Illuminate\Database\Eloquent\Collection;

class AreaRepository implements IAreaRepository
{
    public function getAll(): Collection
    {
        return Area::orderBy('name')->get();
    }

    public function findById(int $id): ?Area
    {
        return Area::find($id);
    }

    public function create(array $data): Area
    {
        return Area::create($data);
    }

    public function update(Area $area, array $data): Area
    {
        $area->update($data);
        return $area->fresh();
    }

    public function delete(Area $area): bool
    {
        return $area->delete();
    }

    public function getWithUserCount(): Collection
    {
        return Area::withCount('users')->orderBy('name')->get();
    }

    public function getWithServicePriceCount(): Collection
    {
        return Area::withCount('servicePrices')->orderBy('name')->get();
    }
} 