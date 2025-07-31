<?php

namespace App\Services;

use App\DTOs\Contact\ContactResponseDTO;
use App\Models\Contact;
use App\Repositories\Interfaces\IContactRepository;
use Illuminate\Database\Eloquent\Collection;

class ContactService
{
    public function __construct(
        private IContactRepository $contactRepository
    ) {}

    public function getAllContacts(): Collection
    {
        return $this->contactRepository->getAll();
    }

    public function getContactById(int $id): ?Contact
    {
        return $this->contactRepository->findById($id);
    }

    public function createContact(array $data): ContactResponseDTO
    {
        $contact = $this->contactRepository->create($data);
        return ContactResponseDTO::fromModel($contact);
    }

    public function deleteContact(int $id): bool
    {
        $contact = $this->contactRepository->findById($id);
        if (!$contact) {
            return false;
        }

        return $this->contactRepository->delete($contact);
    }

    public function getLatestContacts(int $limit = 10): Collection
    {
        return $this->contactRepository->getLatest($limit);
    }

    public function getContactCount(): int
    {
        return $this->contactRepository->getCount();
    }

    public function getContactWithDetails(int $id): ?array
    {
        $contact = $this->contactRepository->findById($id);
        if (!$contact) {
            return null;
        }

        return [
            'contact' => ContactResponseDTO::fromModel($contact)->toArray(),
            'created_at_formatted' => $contact->created_at->format('F j, Y \a\t g:i A'),
            'days_ago' => $contact->created_at->diffInDays(now()),
        ];
    }
} 