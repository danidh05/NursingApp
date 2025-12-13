<?php

namespace App\Services\CategoryHandlers;

use App\DTOs\Request\CreateRequestDTO;
use App\Models\User;
use App\Services\CategoryHandlers\Interfaces\ICategoryRequestHandler;

/**
 * Base class for category-specific request handlers.
 * Provides common functionality and default implementations.
 */
abstract class BaseCategoryRequestHandler implements ICategoryRequestHandler
{
    /**
     * Get common validation rules shared across all categories.
     *
     * @return array
     */
    protected function getCommonRules(): array
    {
        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'problem_description' => ['nullable', 'string'],
            'nurse_gender' => ['nullable', 'string', 'in:male,female,any'],
        ];
    }

    /**
     * Default implementation - can be overridden by specific categories.
     */
    public function afterCreate(\App\Models\Request $request, User $user): void
    {
        // Default: no additional processing needed
    }

    /**
     * Build full_name from first_name and last_name if provided.
     *
     * @param array $data
     * @return string|null
     */
    protected function buildFullName(array $data): ?string
    {
        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        
        if ($firstName && $lastName) {
            return trim("{$firstName} {$lastName}");
        }
        
        if ($firstName) {
            return $firstName;
        }
        
        if ($lastName) {
            return $lastName;
        }
        
        return $data['full_name'] ?? null;
    }
}

