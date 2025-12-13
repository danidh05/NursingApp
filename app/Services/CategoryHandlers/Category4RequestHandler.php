<?php

namespace App\Services\CategoryHandlers;

use App\DTOs\Request\CreateRequestDTO;

/**
 * Category 4: Machines Handler
 * 
 * TODO: Implement category-specific validation rules and DTO mapping
 */
class Category4RequestHandler extends BaseCategoryRequestHandler
{
    public function getValidationRules(): array
    {
        return array_merge($this->getCommonRules(), [
            // TODO: Add Machines-specific validation rules
        ]);
    }

    public function mapToDTO(array $data): CreateRequestDTO
    {
        // TODO: Implement Machines-specific DTO mapping
        return new CreateRequestDTO(
            first_name: $data['first_name'] ?? null,
            last_name: $data['last_name'] ?? null,
            full_name: $this->buildFullName($data) ?? $data['full_name'] ?? null,
            phone_number: $data['phone_number'] ?? null,
            problem_description: $data['problem_description'] ?? null,
            service_ids: [],
            category_id: $data['category_id'] ?? 4,
        );
    }
}

