<?php

namespace App\Policies;

use App\Models\Service;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ServicePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any services.
     */
    public function viewAny(User $user)
    {
        // Both users and admins can view services
        return $user->role_id === 1 || $user->role_id === 2;
    }

    /**
     * Determine whether the user can view the service.
     */
    public function view(User $user, Service $service)
    {
        // Both users and admins can view a specific service
        return $user->role_id === 1 || $user->role_id === 2;
    }

    /**
     * Determine whether the user can create services.
     */
    public function create(User $user)
    {
        // Only admins can create services
        return $user->role_id === 1; // Admin role
    }

    /**
     * Determine whether the user can update the service.
     */
    public function update(User $user, Service $service)
    {
        // Only admins can update services
        return $user->role_id === 1; // Admin role
    }

    /**
     * Determine whether the user can delete the service.
     */
    public function delete(User $user, Service $service)
    {
        // Only admins can delete services
        return $user->role_id === 1; // Admin role
    }
}