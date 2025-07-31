<?php

namespace App\DTOs\Contact;

use App\Models\Contact;

class ContactResponseDTO
{
    public function __construct(
        public int $id,
        public string $first_name,
        public string $second_name,
        public string $address,
        public string $description,
        public ?string $phone_number,
        public string $created_at,
        public string $updated_at,
    ) {}

    public static function fromModel(Contact $contact): self
    {
        return new self(
            id: $contact->id,
            first_name: $contact->first_name,
            second_name: $contact->second_name,
            address: $contact->address,
            description: $contact->description,
            phone_number: $contact->phone_number,
            created_at: $contact->created_at->toISOString(),
            updated_at: $contact->updated_at->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'second_name' => $this->second_name,
            'full_name' => $this->first_name . ' ' . $this->second_name,
            'address' => $this->address,
            'description' => $this->description,
            'phone_number' => $this->phone_number,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
} 