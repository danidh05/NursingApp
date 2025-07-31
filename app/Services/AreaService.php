<?php

namespace App\Services;

use App\DTOs\Area\AreaResponseDTO;
use App\Models\Area;
use App\Repositories\Interfaces\IAreaRepository;
use Illuminate\Database\Eloquent\Collection;

class AreaService
{
    public function __construct(
        private IAreaRepository $areaRepository
    ) {}

    public function getAllAreas(): Collection
    {
        return $this->areaRepository->getAll();
    }

    public function getAreasWithUserCount(): Collection
    {
        return $this->areaRepository->getWithUserCount();
    }

    public function getAreasWithServicePriceCount(): Collection
    {
        return $this->areaRepository->getWithServicePriceCount();
    }

    public function getAreaById(int $id): ?Area
    {
        return $this->areaRepository->findById($id);
    }

    public function createArea(array $data): AreaResponseDTO
    {
        $area = $this->areaRepository->create($data);
        return AreaResponseDTO::fromModel($area);
    }

    public function updateArea(int $id, array $data): ?AreaResponseDTO
    {
        $area = $this->areaRepository->findById($id);
        if (!$area) {
            return null;
        }

        $updatedArea = $this->areaRepository->update($area, $data);
        return AreaResponseDTO::fromModel($updatedArea);
    }

    public function deleteArea(int $id): bool
    {
        $area = $this->areaRepository->findById($id);
        if (!$area) {
            return false;
        }

        // Check if area has users
        if ($area->users()->count() > 0) {
            throw new \Exception('Cannot delete area that has users assigned to it.');
        }

        // Check if area has service prices
        if ($area->servicePrices()->count() > 0) {
            throw new \Exception('Cannot delete area that has service prices configured.');
        }

        return $this->areaRepository->delete($area);
    }

    public function getAreaWithDetails(int $id): ?array
    {
        $area = $this->areaRepository->findById($id);
        if (!$area) {
            return null;
        }

        return [
            'area' => AreaResponseDTO::fromModel($area)->toArray(),
            'user_count' => $area->users()->count(),
            'service_price_count' => $area->servicePrices()->count(),
            'users' => $area->users()->with('role')->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->name ?? 'Unknown',
                    'created_at' => $user->created_at->toISOString(),
                ];
            }),
            'service_prices' => $area->servicePrices()->with('service')->get()->map(function ($servicePrice) {
                return [
                    'id' => $servicePrice->id,
                    'service_name' => $servicePrice->service->name ?? 'Unknown Service',
                    'price' => $servicePrice->price,
                    'created_at' => $servicePrice->created_at->toISOString(),
                ];
            }),
        ];
    }
} 