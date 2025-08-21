<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\Nurse;
use App\Models\Request;
use App\Models\Service;
use App\Models\Category;
use App\Models\About;
use App\Models\Rating;
use App\Models\Slider;
use App\Models\Popup;
use App\Policies\UserPolicy;
use App\Policies\NursePolicy;
use App\Policies\RequestPolicy;
use App\Policies\ServicePolicy;
use App\Policies\CategoryPolicy;
use App\Policies\AboutPolicy;
use App\Policies\NurseRatingPolicy;
use App\Policies\SliderPolicy;
use App\Policies\PopupPolicy;
use App\Models\ChatThread;
use App\Policies\ChatThreadPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Nurse::class => NursePolicy::class,
        Request::class => RequestPolicy::class,
        Service::class => ServicePolicy::class,
        Category::class => CategoryPolicy::class,
        About::class => AboutPolicy::class,
        Rating::class => NurseRatingPolicy::class,
        Slider::class => SliderPolicy::class,
        Popup::class => PopupPolicy::class,
        ChatThread::class => ChatThreadPolicy::class,
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