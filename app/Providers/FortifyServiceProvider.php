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
        // ⚠️ **Fortify no registra ninguna ruta en esta aplicación.**
        //
        // Va en `register()` y no en `boot()` a propósito: Fortify registra sus
        // rutas en su propio `boot()`, y todos los `register()` corren antes que
        // cualquier `boot()`.
        //
        // Por qué se quitan:
        //
        //  · **No las usa nada.** El login y el logout reales son los de
        //    Filament (`/admin/login`, `/panel/login`, `/admin/logout`,
        //    `/panel/logout`), no hay registro público y el panel no expone
        //    ninguna pantalla de doble factor. De Fortify se usan las *acciones*
        //    (`App\Actions\Fortify\*`) y el trait `TwoFactorAuthenticatable`
        //    del modelo `User`, que no dependen de las rutas.
        //
        //  · **`POST /login` era una puerta de atrás.** Autenticaba saltándose
        //    el login de Filament y, con él, el reCAPTCHA que protege el
        //    formulario. Un login sin reCAPTCHA es exactamente lo que la
        //    política del proyecto no quiere.
        //
        //  · Las de vista (`GET /login`, `/two-factor-challenge`,
        //    `/user/confirm-password`) ya no existían: `config/fortify.php` las
        //    apagó con `views => false` porque respondían 500. Esto quita
        //    también las POST y las de doble factor, que quedaban colgando.
        //
        // Para volver a activarlas: quitar esta línea. Los limitadores de abajo
        // (`login` y `two-factor`) se conservan porque son los que Fortify pide
        // en cuanto se reactiva.
        Fortify::ignoreRoutes();
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

        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;

            return Limit::perMinute(5)->by($email.$request->ip());
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
