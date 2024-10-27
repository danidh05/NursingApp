<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Request as NurseRequest;

class RequestPolicy
{
    /**
     * Determine whether the user can view any requests.
     * Admin can view all non-deleted requests; Users can view their own requests (including soft-deleted).
     */
    public function viewAny(User $user)
    {
        if ($user->role_id === 1) {
            // Admin can view only non-soft-deleted requests
            return true;
        } else {
            // User can view their own requests, including soft-deleted
            return $user->role_id === 2;
        }
    }
    

    /**
     * Determine whether the user can view the request.
     * Admins cannot view soft-deleted requests, users can view their own soft-deleted requests.
     */
    public function view(User $user, NurseRequest $request)
    {
        // Users can view their own soft-deleted requests
       

        // Admin can view non-deleted requests, users can view their own requests
        return $user->role_id === 1 || $user->id === $request->user_id;
    }

    /**
     * Determine whether the user can create requests.
     * Only users (role_id 2) can create requests.
     */
    public function create(User $user)
    {
        return $user->role_id === 2; // Assuming only users (role_id 2) create requests
    }

    /**
     * Determine whether the user can update the request.
     * Only admins (role_id 1) can update requests.
     */
    public function update(User $user, NurseRequest $request)
    {
        return $user->role_id === 1; // Only admins can update requests
    }

    /**
     * Determine whether the user can delete the request.
     * Only admins (role_id 1) can delete requests.
     */
    public function delete(User $user, NurseRequest $request)
    {
        return $user->role_id === 1; // Only admins can delete requests
    }
}