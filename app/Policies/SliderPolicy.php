<?php

namespace App\Policies;

use App\Models\Slider;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SliderPolicy
{
    use HandlesAuthorization;

    // Determine whether the user can view any sliders (both Users and Admins).
    public function viewAny(User $user)
    {
        return $user->role_id === 1 || $user->role_id === 2; // Assuming 1 = Admin, 2 = User
    }

    // Determine whether the user can view a specific slider (both Users and Admins).
    public function view(User $user, Slider $slider)
    {
        return $user->role_id === 1 || $user->role_id === 2;
    }

    // Determine whether the user can create sliders (Admins only).
    public function create(User $user)
    {
        return $user->role_id === 1; // Only Admins can create
    }

    // Determine whether the user can update sliders (Admins only).
    public function update(User $user, Slider $slider)
    {
        return $user->role_id === 1; // Only Admins can update
    }

    // Determine whether the user can delete sliders (Admins only).
    public function delete(User $user, Slider $slider)
    {
        return $user->role_id === 1; // Only Admins can delete
    }
}