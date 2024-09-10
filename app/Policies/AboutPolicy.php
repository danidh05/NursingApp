<?php


namespace App\Policies;

use App\Models\User;
use App\Models\About;
use Illuminate\Auth\Access\HandlesAuthorization;

class AboutPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can update the "About Us" information.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\About  $about
     * @return mixed
     */
    public function update(User $user, About $about)
    {
        // Only allow users with the admin role to update the About information
        return $user->role_id === 1; // Assuming role_id 1 is for admins
    }
}