<?php

namespace App\Repositories\Interfaces;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Collection;

interface IContactRepository
{
    public function getAll(): Collection;
    public function findById(int $id): ?Contact;
    public function create(array $data): Contact;
    public function delete(Contact $contact): bool;
    public function getLatest(int $limit = 10): Collection;
    public function getCount(): int;
} 