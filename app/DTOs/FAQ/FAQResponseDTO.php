<?php

namespace App\DTOs\FAQ;

use App\Models\FAQ;

class FAQResponseDTO
{
    public function __construct(
        public int $id,
        public string $question,
        public string $answer,
        public int $order,
        public bool $is_active,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(FAQ $faq): self
    {
        return new self(
            id: $faq->id,
            question: $faq->question,
            answer: $faq->answer,
            order: $faq->order,
            is_active: $faq->is_active,
            created_at: $faq->created_at->toISOString(),
            updated_at: $faq->updated_at->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 