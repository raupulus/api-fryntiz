<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\ApiToken;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;

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
        // Soporte para comentarios en colecciones (e.g. $table->timestamps()->comment(...)) en Laravel 11/12/13
        Collection::macro('comment', function ($value) {
            return $this->each(fn ($column) => method_exists($column, 'comment') ? $column->comment($value) : null);
        });

        // Global styling for Filament buttons
        CreateAction::configureUsing(function (CreateAction $action) {
            $action->color('success');
        });
        EditAction::configureUsing(function (EditAction $action) {
            $action->color('primary');
        });

        // Custom Save action configuration (they usually are primary, turn them success)
        Action::configureUsing(function (Action $action) {
            if (in_array($action->getName(), ['save', 'create'])) {
                $action->color('success');
            }
        });

        // Usar modelo ApiToken personalizado para Filament Resource (fix_10 / fase 13).
        Sanctum::usePersonalAccessTokenModel(ApiToken::class);

        // Lazy loading estricto fuera de producción: lanza excepción al detectar
        // una relación cargada bajo demanda (N+1) para corregirla con eager loading.
        // En producción NUNCA lanza (no afecta a usuarios finales).
        Model::preventLazyLoading(! app()->isProduction());

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

        if (app()->environment('testing')) {
            RateLimiter::for('api', fn () => Limit::none());
            RateLimiter::for('sensor-data', fn () => Limit::none());
            RateLimiter::for('contact', fn () => Limit::none());
            RateLimiter::for('api-auth', fn () => Limit::none());
            RateLimiter::for('api-store', fn () => Limit::none());
            RateLimiter::for('api-store-batch', fn () => Limit::none());
        } else {
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
}
