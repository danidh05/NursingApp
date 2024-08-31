<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Nurse;  // Import Nurse model
use App\Policies\UserPolicy;
use App\Policies\NursePolicy;  // Import NursePolicy

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => UserPolicy::class,  // Map the User model to the UserPolicy
        Nurse::class => NursePolicy::class,  // Map the Nurse model to the NursePolicy
        Request::class => RequestPolicy::class,
        Service::class => ServicePolicy::class,
        Category::class => CategoryPolicy::class,

    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Additional Gates can be defined here if needed
    }
}