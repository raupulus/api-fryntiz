<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Class AppServiceProvider
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Registrar lazy loading como advertencia en desarrollo (sin lanzar excepción)
        // TODO: Corregir queries N+1 y activar preventLazyLoading estricto
        Model::handleLazyLoadingViolationUsing(function (Model $model, string $relation) {
            Log::warning("Lazy loading [{$relation}] on model [" . get_class($model) . "]");
        });

        // Gate global: superadmin tiene acceso a todo
        Gate::before(function ($user, $ability) {
            if ($user->isSuperAdmin()) {
                return true;
            }
        });

        // Gate: acceso al panel de administración
        Gate::define('access-admin-panel', function ($user) {
            return $user->isSuperAdmin();
        });

        // Gate: gestionar configuración global
        Gate::define('manage-settings', function ($user) {
            return $user->isAdmin();
        });

        // Gate: ver estadísticas globales
        Gate::define('view-statistics', function ($user) {
            return $user->isAdmin();
        });

        // Rate limiter general para API
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter estricto para escritura de datos de sensores
        RateLimiter::for('sensor-data', function (Request $request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter para contacto
        RateLimiter::for('contact', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        // Rate limiter para autenticación API V2 (prevenir fuerza bruta)
        RateLimiter::for('api-auth', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Rate limiter para stores IoT API V2
        RateLimiter::for('api-store', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Rate limiter para batch stores API V2
        RateLimiter::for('api-store-batch', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });
    }
}
