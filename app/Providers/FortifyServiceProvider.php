<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
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
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // `GET /login` existe porque `config/fortify.php` tiene `views => true`
        // —hace falta para las vistas de recuperación de contraseña y de
        // verificación de correo—, pero **aquí no hay vista de login propia**:
        // el login real siempre pasa por Filament.
        //
        // Sin registrar nada, esa ruta reventaba con un 500 en producción
        // (`Target [Laravel\Fortify\Contracts\LoginViewResponse] is not
        // instantiable`) en cuanto alguien entraba a /login: un enlace viejo, un
        // marcador o un bot rastreando. `redirectGuestsTo()` en
        // `bootstrap/app.php` evita que Laravel mande ahí a los invitados, pero
        // no impide que se pida la URL a mano.
        //
        // Se manda al login del panel Tenant, que es el mismo destino que usa
        // `redirectGuestsTo()`: abierto a cualquier usuario activo, admins
        // incluidos.
        Fortify::loginView(fn () => redirect()->route('filament.tenant.auth.login'));

        // El mismo agujero por otras dos puertas: Fortify registra estas dos
        // rutas y ninguna tiene vista aquí. No las usa nada del proyecto —el
        // panel gestiona su propio doble factor—, pero se pueden alcanzar:
        // `two-factor-challenge` con una sesión de 2FA a medias tras un
        // `POST /login`, y `user/confirm-password` con sesión iniciada. Sin
        // esto, las dos responden 500 en vez de llevar a ninguna parte.
        Fortify::twoFactorChallengeView(fn () => redirect()->route('filament.tenant.auth.login'));
        Fortify::confirmPasswordView(fn () => redirect()->route('filament.tenant.auth.login'));

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
