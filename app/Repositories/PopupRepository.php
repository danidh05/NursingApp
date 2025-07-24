<?php

namespace App\Repositories;

use App\Models\Popup;
use App\Repositories\Interfaces\IPopupRepository;
use Illuminate\Database\Eloquent\Collection;
use App\Models\User;

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

    public function getActiveForUser(?User $user = null): ?Popup
    {
        if ($user) {
            // First check for user-specific popups
            $userSpecificPopup = Popup::activeForUser($user)
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->first();
            
            if ($userSpecificPopup) {
                return $userSpecificPopup;
            }
        }

        // Fall back to global popups
        return Popup::activeForUser($user)
            ->whereNull('user_id')
            ->orderBy('created_at', 'desc')
            ->first();
    }
} 