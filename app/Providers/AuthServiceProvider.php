<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\DailyHandover;
use App\Policies\ActivityPolicy;
use App\Policies\DailyHandoverPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Activity::class => ActivityPolicy::class,
        DailyHandover::class => DailyHandoverPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define any additional gates here if needed
        Gate::define('view-admin-panel', function ($user) {
            return $user->is_admin;
        });

        Gate::define('manage-users', function ($user) {
            return $user->is_admin;
        });

        Gate::define('view-all-reports', function ($user) {
            return $user->is_admin;
        });
    }
}
