<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

/**
 * Class AuthServiceProvider
 */
class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // En Laravel 11+ las policies se auto-descubren por convención.
        // Si necesitas mapeos explícitos, usa Gate::policy().

        Gate::define('viewWebSocketsDashboard', function ($user = null) {
            return $user && in_array($user->role_id, [1, 2]);
        });
    }
}
