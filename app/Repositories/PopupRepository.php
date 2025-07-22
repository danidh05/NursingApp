<?php

namespace App\Repositories;

use App\Models\Popup;
use App\Repositories\Interfaces\IPopupRepository;
use Illuminate\Database\Eloquent\Collection;

class PopupRepository implements IPopupRepository
{
    public function getAll(): Collection
    {
        return Popup::all();
    }

    public function findById(int $id): Popup
    {
        return Popup::findOrFail($id);
    }

    public function create(array $data): Popup
    {
        return Popup::create($data);
    }

    public function update(int $id, array $data): Popup
    {
        $popup = $this->findById($id);
        $popup->update($data);
        return $popup; // Remove unnecessary fresh() call
    }

    public function delete(int $id): void
    {
        $popup = $this->findById($id);
        $popup->delete();
    }

    public function getActive(): ?Popup
    {
        return Popup::active()
            ->orderBy('created_at', 'desc')
            ->first();
    }
} 