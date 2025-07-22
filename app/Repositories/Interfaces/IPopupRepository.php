<?php

namespace App\Repositories\Interfaces;

use App\Models\Popup;
use Illuminate\Database\Eloquent\Collection;

interface IPopupRepository
{
    public function getAll(): Collection;
    public function findById(int $id): Popup;
    public function create(array $data): Popup;
    public function update(int $id, array $data): Popup;
    public function delete(int $id): void;
    public function getActive(): ?Popup;
} 