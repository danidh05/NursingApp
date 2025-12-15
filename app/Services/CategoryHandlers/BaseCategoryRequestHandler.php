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
            // Address fields (common for all categories)
            'use_saved_address' => ['nullable', 'boolean'], // Laravel handles "true"/"false" strings automatically
            'address_city' => ['nullable', 'string', 'max:255'],
            'address_street' => ['nullable', 'string', 'max:255'],
            'address_building' => ['nullable', 'string', 'max:255'],
            'address_additional_information' => ['nullable', 'string'],
            'additional_information' => ['nullable', 'string'],
        ];
    }

    /**
     * Normalize boolean values from form-data (which sends strings).
     * Converts "true", "1", "on", "yes" to true, everything else to false.
     *
     * @param mixed $value
     * @return bool|null
     */
    protected function normalizeBoolean($value): ?bool
    {
        if ($value === null) {
            return null;
        }
        
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', '1', 'on', 'yes'], true);
        }
        
        return (bool) $value;
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

