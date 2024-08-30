<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Nurse;

class NursePolicy
{
    /**
     * Determine whether the user can view any nurses.
     */
    public function viewAny(User $user)
    {
        return true; // All authenticated users can view the nurses
    }

    /**
     * Determine whether the user can view the nurse.
     */
    public function view(User $user, Nurse $nurse)
    {
        return true; // All authenticated users can view a nurse's details
    }

    /**
     * Determine whether the user can create nurses.
     */
    public function create(User $user)
    {
        return $user->role->name === 'admin'; // Only admins can create nurses
    }

    /**
     * Determine whether the user can update the nurse.
     */
    public function update(User $user, Nurse $nurse)
    {
        return $user->role->name === 'admin'; // Only admins can update nurses
    }

    /**
     * Determine whether the user can delete the nurse.
     */
    public function delete(User $user, Nurse $nurse)
    {
        return $user->role->name === 'admin'; // Only admins can delete nurses
    }

    /**
     * Determine whether the user can restore the nurse.
     */
    public function restore(User $user, Nurse $nurse)
    {
        return $user->role->name === 'admin'; // Only admins can restore nurses
    }

    /**
     * Determine whether the user can permanently delete the nurse.
     */
    public function forceDelete(User $user, Nurse $nurse)
    {
        return $user->role->name === 'admin'; // Only admins can force delete nurses
    }
}