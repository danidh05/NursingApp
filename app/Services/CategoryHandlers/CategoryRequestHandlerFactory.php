<?php

namespace App\Services\CategoryHandlers;

use App\Services\CategoryHandlers\Interfaces\ICategoryRequestHandler;
use InvalidArgumentException;

/**
 * Factory to get the appropriate category request handler based on category_id.
 * 
 * Categories:
 * 1. Service Request (Category1RequestHandler)
 * 2. Tests (Category2RequestHandler) - TODO
 * 3. Rays (Category3RequestHandler) - TODO
 * 4. Machines (Category4RequestHandler) - TODO
 * 5. Physiotherapist (Category5RequestHandler) - TODO
 * 6. Offers (Category6RequestHandler) - TODO
 * 7. Duties (Category7RequestHandler) - TODO
 * 8. Doctors (Category8RequestHandler) - TODO
 */
class CategoryRequestHandlerFactory
{
    /**
     * Get the handler for a specific category.
     *
     * @param int $categoryId
     * @return ICategoryRequestHandler
     * @throws InvalidArgumentException
     */
    public static function getHandler(int $categoryId): ICategoryRequestHandler
    {
        return match ($categoryId) {
            1 => new Category1RequestHandler(), // Service Request
            2 => new Category2RequestHandler(), // Tests
            3 => new Category3RequestHandler(), // Rays
            4 => new Category4RequestHandler(), // Machines
            5 => new Category5RequestHandler(), // Physiotherapist
            6 => new Category6RequestHandler(), // Offers
            7 => new Category7RequestHandler(), // Duties
            8 => new Category8RequestHandler(), // Doctors
            default => throw new InvalidArgumentException("Category handler for category_id {$categoryId} is not implemented. Valid categories are 1-8."),
        };
    }

    /**
     * Get validation rules for a specific category.
     *
     * @param int $categoryId
     * @return array
     */
    public static function getValidationRules(int $categoryId): array
    {
        $handler = self::getHandler($categoryId);
        return $handler->getValidationRules();
    }
}

