<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Nurse;
use App\Models\Rating;

class NurseRatingPolicy
{
    /**
     * Determine if the user can rate the nurse.
     */
    public function rate(User $user, Nurse $nurse)
    {
        // Ensure user hasn't already rated this nurse
        return !$nurse->ratings()->where('user_id', $user->id)->exists();
    }
}