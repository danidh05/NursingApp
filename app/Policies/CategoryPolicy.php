<?php


namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CategoryPolicy
{
    use HandlesAuthorization;

    // Determine whether the user can view any categories (both Users and Admins).
    public function viewAny(User $user)
    {
        return $user->role_id === 1 || $user->role_id === 2; // Assuming 1 = Admin, 2 = User
    }

    // Determine whether the user can view a specific category (both Users and Admins).
    public function view(User $user, Category $category)
    {
        return $user->role_id === 1 || $user->role_id === 2;
    }

    // Determine whether the user can create categories (Admins only).
    public function create(User $user)
    {
        return $user->role_id === 1; // Only Admins can create
    }

    // Determine whether the user can update categories (Admins only).
    public function update(User $user, Category $category)
    {
        return $user->role_id === 1; // Only Admins can update
    }

    // Determine whether the user can delete categories (Admins only).
    public function delete(User $user, Category $category)
    {
        return $user->role_id === 1; // Only Admins can delete
    }
}