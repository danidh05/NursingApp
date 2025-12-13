<?php

namespace App\Services\CategoryHandlers\Interfaces;

use App\DTOs\Request\CreateRequestDTO;
use App\Models\User;

/**
 * Interface for category-specific request handlers.
 * Each category can have different validation rules, DTO mapping, and processing logic.
 */
interface ICategoryRequestHandler
{
    /**
     * Get validation rules specific to this category.
     *
     * @return array
     */
    public function getValidationRules(): array;

    /**
     * Process and map request data to CreateRequestDTO for this category.
     * This allows each category to have different required/optional fields.
     *
     * @param array $data
     * @return CreateRequestDTO
     */
    public function mapToDTO(array $data): CreateRequestDTO;

    /**
     * Get category-specific additional processing after request creation.
     * This can be used for category-specific business logic.
     *
     * @param \App\Models\Request $request
     * @param User $user
     * @return void
     */
    public function afterCreate(\App\Models\Request $request, User $user): void;
}

