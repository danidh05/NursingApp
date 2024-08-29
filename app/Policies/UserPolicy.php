<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
/**
* Determine whether the user can view any users (Admin only).
*/
public function viewAny(User $authUser)
{
return $authUser->role->name === 'admin';
}

/**
* Determine whether the user can view a specific user (Admin or the user themselves).
*/
public function view(User $authUser, User $user)
{
return $authUser->id === $user->id || $authUser->role->name === 'admin';
}

/**
* Determine whether the user can create a new user (Admin only).
*/
public function create(User $authUser)
{
return $authUser->role->name === 'admin';
}

/**
* Determine whether the user can update a specific user (Admin or the user themselves).
*/
public function update(User $authUser, User $user)
{
return $authUser->id === $user->id || $authUser->role->name === 'admin';
}

/**
* Determine whether the user can delete a specific user (Admin only).
*/
public function delete(User $authUser, User $user)
{
return $authUser->role->name === 'admin';
}
}