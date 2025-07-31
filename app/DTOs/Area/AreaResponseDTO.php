<?php

namespace App\DTOs\Area;

use App\Models\Area;

class AreaResponseDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(Area $area): self
    {
        return new self(
            id: $area->id,
            name: $area->name,
            created_at: $area->created_at->toISOString(),
            updated_at: $area->updated_at->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 