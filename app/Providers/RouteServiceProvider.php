<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use function base_path;

/**
 * Class RouteServiceProvider
 *
 * @package App\Providers
 */
class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * The controller namespace for the application.
     *
     * When present, controller route declarations will automatically be prefixed with this namespace.
     *
     * @var string|null
     */
    // protected $namespace = 'App\\Http\\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        Route::model('user_id', User::class);

        $this->routes(function () {
            Route::prefix('api')
                ->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/api/v1.php'));

            Route::middleware('web')
                ->prefix('panel')
                ->namespace($this->namespace)
                ->group(base_path('routes/dashboard.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            Route::prefix('api/weatherstation/v1')
                //->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/weather_station/v1.php'));

            Route::prefix('weatherstation')
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/weather_station/web.php'));

            Route::prefix('api/keycounter/v1')
                //->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/keycounter/v1.php'));

            Route::prefix('keycounter')
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/keycounter/web.php'));

            Route::prefix('api/smartplant/v1')
                //->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/smart_plant/v1.php'));

            Route::prefix('smartplant')
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/smart_plant/web.php'));

            Route::prefix('api/airflight/v1')
                //->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/airflight/v1.php'));

            Route::prefix('airflight')
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/airflight/web.php'));

            Route::prefix('api/cv/v1')
                //->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/cv/v1.php'));

            Route::prefix('cv')
                ->middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/cv/web.php'));

            Route::prefix('api/hardware/v1')
                //->middleware('api')
                ->namespace($this->namespace)
                ->group(base_path('routes/hardware/v1.php'));

            Route::prefix('webhook')
                //->middleware('TODO → New custom middleware')
                ->namespace($this->namespace)
                ->group(base_path('routes/webhook.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
}
