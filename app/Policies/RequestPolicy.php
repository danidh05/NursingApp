<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Request;

class RequestPolicy
{
    /**
     * Determine whether the user can view any requests.
     */
    public function viewAny(User $user)
    {
        return $user->role->name === 'admin';
    }

    /**
     * Determine whether the user can view the request.
     */
    public function view(User $user, Request $request)
    {
        // Admins can view all requests, users can only view their own requests
        return $user->role->name === 'admin' || $user->id === $request->user_id;
    }

    /**
     * Determine whether the user can create requests.
     */
    public function create(User $user)
    {
        return $user->role->name === 'user'; // Assuming only users create requests
    }

    /**
     * Determine whether the user can update the request.
     */
    public function update(User $user, Request $request)
    {
        // Only admins can update requests
        return $user->role->name === 'admin';
    }

    /**
     * Determine whether the user can delete the request.
     */
    public function delete(User $user, Request $request)
    {
        // Only admins can delete requests
        return $user->role->name === 'admin';
    }
}