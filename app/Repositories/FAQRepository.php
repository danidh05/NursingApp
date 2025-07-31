<?php

namespace App\Repositories;

use App\Models\FAQ;
use App\Repositories\Interfaces\IFAQRepository;
use Illuminate\Database\Eloquent\Collection;

class FAQRepository implements IFAQRepository
{
    /**
     * Get all FAQs ordered by order field.
     */
    public function getAll(): Collection
    {
        return FAQ::ordered()->get();
    }

    /**
     * Get all active FAQs ordered by order field.
     */
    public function getActive(): Collection
    {
        return FAQ::getActiveOrdered();
    }

    /**
     * Find FAQ by ID.
     */
    public function findById(int $id): ?FAQ
    {
        return FAQ::find($id);
    }

    /**
     * Create a new FAQ.
     */
    public function create(array $data): FAQ
    {
        return FAQ::create($data);
    }

    /**
     * Update an existing FAQ.
     */
    public function update(FAQ $faq, array $data): FAQ
    {
        $faq->update($data);
        return $faq->fresh();
    }

    /**
     * Delete an FAQ.
     */
    public function delete(FAQ $faq): bool
    {
        return $faq->delete();
    }

    /**
     * Get the next order number for new FAQs.
     */
    public function getNextOrder(): int
    {
        $maxOrder = FAQ::max('order') ?? 0;
        return $maxOrder + 1;
    }
} 