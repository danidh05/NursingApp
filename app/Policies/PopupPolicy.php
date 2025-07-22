<?php

namespace App\Policies;

use App\Models\Popup;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PopupPolicy
{
    use HandlesAuthorization;

    // Determine whether the user can view any popups (both Users and Admins).
    public function viewAny(User $user)
    {
        return $user->role_id === 1 || $user->role_id === 2; // Assuming 1 = Admin, 2 = User
    }

    // Determine whether the user can view a specific popup (both Users and Admins).
    public function view(User $user, Popup $popup)
    {
        return $user->role_id === 1 || $user->role_id === 2;
    }

    // Determine whether the user can create popups (Admins only).
    public function create(User $user)
    {
        return $user->role_id === 1; // Only Admins can create
    }

    // Determine whether the user can update popups (Admins only).
    public function update(User $user, Popup $popup)
    {
        return $user->role_id === 1; // Only Admins can update
    }

    // Determine whether the user can delete popups (Admins only).
    public function delete(User $user, Popup $popup)
    {
        return $user->role_id === 1; // Only Admins can delete
    }
}