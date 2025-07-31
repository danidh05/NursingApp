<?php

namespace App\Services;

use App\DTOs\FAQ\FAQResponseDTO;
use App\Models\FAQ;
use App\Repositories\Interfaces\IFAQRepository;
use Illuminate\Database\Eloquent\Collection;

class FAQService
{
    public function __construct(
        private IFAQRepository $faqRepository
    ) {}

    /**
     * Get all FAQs.
     */
    public function getAllFAQs(): Collection
    {
        return $this->faqRepository->getAll();
    }

    /**
     * Get all active FAQs.
     */
    public function getActiveFAQs(): Collection
    {
        return $this->faqRepository->getActive();
    }

    /**
     * Get FAQ by ID.
     */
    public function getFAQById(int $id): ?FAQ
    {
        return $this->faqRepository->findById($id);
    }

    /**
     * Create a new FAQ.
     */
    public function createFAQ(array $data): FAQResponseDTO
    {
        // Set default order if not provided
        if (!isset($data['order'])) {
            $data['order'] = $this->faqRepository->getNextOrder();
        }

        // Set default active status if not provided
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        $faq = $this->faqRepository->create($data);
        return FAQResponseDTO::fromModel($faq);
    }

    /**
     * Update an existing FAQ.
     */
    public function updateFAQ(int $id, array $data): ?FAQResponseDTO
    {
        $faq = $this->faqRepository->findById($id);
        
        if (!$faq) {
            return null;
        }

        $updatedFAQ = $this->faqRepository->update($faq, $data);
        return FAQResponseDTO::fromModel($updatedFAQ);
    }

    /**
     * Delete an FAQ.
     */
    public function deleteFAQ(int $id): bool
    {
        $faq = $this->faqRepository->findById($id);
        
        if (!$faq) {
            return false;
        }

        return $this->faqRepository->delete($faq);
    }

    /**
     * Toggle FAQ active status.
     */
    public function toggleFAQStatus(int $id): ?FAQResponseDTO
    {
        $faq = $this->faqRepository->findById($id);
        
        if (!$faq) {
            return null;
        }

        $updatedFAQ = $this->faqRepository->update($faq, [
            'is_active' => !$faq->is_active
        ]);

        return FAQResponseDTO::fromModel($updatedFAQ);
    }

    /**
     * Reorder FAQs.
     */
    public function reorderFAQs(array $faqIds): bool
    {
        foreach ($faqIds as $index => $faqId) {
            $faq = $this->faqRepository->findById($faqId);
            if ($faq) {
                $this->faqRepository->update($faq, ['order' => $index + 1]);
            }
        }

        return true;
    }
} 