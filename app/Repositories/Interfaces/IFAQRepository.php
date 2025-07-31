<?php

namespace App\Repositories\Interfaces;

use App\Models\FAQ;
use Illuminate\Database\Eloquent\Collection;

interface IFAQRepository
{
    /**
     * Get all FAQs ordered by order field.
     */
    public function getAll(): Collection;

    /**
     * Get all active FAQs ordered by order field.
     */
    public function getActive(): Collection;

    /**
     * Find FAQ by ID.
     */
    public function findById(int $id): ?FAQ;

    /**
     * Create a new FAQ.
     */
    public function create(array $data): FAQ;

    /**
     * Update an existing FAQ.
     */
    public function update(FAQ $faq, array $data): FAQ;

    /**
     * Delete an FAQ.
     */
    public function delete(FAQ $faq): bool;

    /**
     * Get the next order number for new FAQs.
     */
    public function getNextOrder(): int;
} 