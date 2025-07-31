<?php

namespace App\Repositories;

use App\Models\Contact;
use App\Repositories\Interfaces\IContactRepository;
use Illuminate\Database\Eloquent\Collection;

class ContactRepository implements IContactRepository
{
    public function getAll(): Collection
    {
        return Contact::latest()->get();
    }

    public function findById(int $id): ?Contact
    {
        return Contact::find($id);
    }

    public function create(array $data): Contact
    {
        return Contact::create($data);
    }

    public function delete(Contact $contact): bool
    {
        return $contact->delete();
    }

    public function getLatest(int $limit = 10): Collection
    {
        return Contact::latest()->limit($limit)->get();
    }

    public function getCount(): int
    {
        return Contact::count();
    }
} 