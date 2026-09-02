<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\ApiToken;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;
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
        // Proxies de confianza.
        //
        // Sin esto, detrás de nginx/Cloudflare `$request->ip()` devuelve la IP
        // del proxy, así que TODOS los rate limit por IP (login, contacto,
        // newsletter) pasan a ser un cupo global compartido por todos los
        // visitantes: ni frenan a un atacante ni dejan pasar el tráfico bueno.
        //
        // Va aquí y no en `bootstrap/app.php` a propósito. Allí el callback de
        // `withMiddleware()` se ejecuta antes de que el contenedor tenga el
        // servicio `config`, así que el valor sólo podía leerse con `env()`; y
        // `env()` devuelve `null` en el servidor, porque el despliegue hace
        // `config:cache` y entonces Laravel se salta la carga del `.env`.
        // Resultado: `TRUSTED_PROXIES` se ignoraba en silencio y siempre se
        // aplicaba el valor por defecto. Con nginx en la misma máquina no se
        // notaba —el default cubre los rangos privados—, pero cualquier proxy
        // con IP pública por delante (Cloudflare) dejaba de ser de confianza
        // sin un solo aviso.
        //
        // `boot()` corre con la config ya cargada y antes del pipeline de
        // middleware, que es justo lo que hace falta.
        //
        // Por defecto se confía en los rangos privados (la red de Docker y el
        // nginx del propio host), no en "*": si la aplicación quedara alcanzable
        // directamente, "*" permitiría falsificar la IP con una cabecera.
        TrustProxies::at(array_values(array_filter(array_map(
            'trim',
            explode(',', (string) config('app.trusted_proxies'))
        ))));

        TrustProxies::withHeaders(
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO
        );

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

        // Un token de un usuario desactivado no autentica, aunque el token siga
        // vivo. Es la palanca para cortar de golpe todos los cacharros de una
        // cuenta comprometida sin tener que borrar token a token.
        Sanctum::authenticateAccessTokensUsing(
            static function (PersonalAccessToken $accessToken, bool $isValid): bool {
                if (! $isValid) {
                    return false;
                }

                $tokenable = $accessToken->tokenable;

                return ! ($tokenable instanceof User) || (bool) $tokenable->is_active;
            }
        );

        // Lazy loading estricto fuera de producción: lanza excepción al detectar
        // una relación cargada bajo demanda (N+1) para corregirla con eager loading.
        // En producción NUNCA lanza (no afecta a usuarios finales).
        Model::preventLazyLoading(! app()->isProduction());

        // Huso horario de VISUALIZACIÓN (D100).
        //
        // La aplicación corre y guarda en UTC —`config/app.php` pone 'UTC'
        // literal, no `env()`, así que `APP_TIMEZONE` es inerte a propósito—.
        // Pero mostrar UTC en el panel obliga a restar una o dos horas mentales
        // según la época del año.
        //
        // `FilamentTimezone` afecta a todas las columnas `dateTime()`, a los
        // `DateTimePicker` y a las infolists del panel. No toca la base de datos
        // ni la salida de la API, que siguen en UTC con `Z`.
        FilamentTimezone::set(config('app.display_timezone', 'Europe/Madrid'));

        // Política de contraseñas por defecto.
        //
        // `Password::defaults()` se usa en tres sitios (panel de usuarios,
        // perfil y registro por API) y nunca se había configurado, así que
        // equivalía a `min:8` a secas (fix1 #11). Se define una sola vez aquí
        // y la heredan los tres.
        Password::defaults(static function (): Password {
            $regla = Password::min(12)->letters()->mixedCase()->numbers();

            return app()->isProduction() ? $regla->uncompromised() : $regla;
        });

        // Gate global: superadmin tiene acceso a todo.
        //
        // Con una excepción que importa mucho: si la petición viene de un token
        // de dispositivo IoT, el atajo NO se aplica. El dueño de los cacharros
        // es superadmin, así que sin esto el token de una estación heredaría
        // "acceso a todo" y las 16 policies quedarían anuladas justo para el
        // principal del que hay que defenderse. Devolver null deja que la
        // policy correspondiente decida con sus propias reglas de propiedad.
        Gate::before(function ($user, $ability) {
            if (TokenAbilities::deviceRequest($user)) {
                return null;
            }

            if ($user->isSuperAdmin()) {
                return true;
            }

            return null;
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
            RateLimiter::for('api-global', fn () => Limit::none());
            RateLimiter::for('api-fallback', fn () => Limit::none());
            RateLimiter::for('api', fn () => Limit::none());
            RateLimiter::for('file-resize', fn () => Limit::none());
            RateLimiter::for('sensor-data', fn () => Limit::none());
            RateLimiter::for('contact', fn () => Limit::none());
            RateLimiter::for('api-auth', fn () => Limit::none());
            RateLimiter::for('api-store', fn () => Limit::none());
            RateLimiter::for('api-store-batch', fn () => Limit::none());
        } else {
            // Los números salen de config/rate_limits.php, que explica de dónde
            // sale cada uno. Antes estaban escritos aquí a mano.

            // Techo de TODO el grupo `api`, lo pida la ruta o no (AR-S01).
            //
            // Se aplica desde `bootstrap/app.php` con `throttleApi('api-global')`.
            // Desde Laravel 11 el grupo `api` no trae throttle de fábrica, así
            // que sin esto las rutas públicas de lectura no tenían ninguno.
            //
            // No sustituye a los limitadores de abajo: el middleware de ruta
            // corre después del de grupo, así que sobre una escritura IoT
            // siguen aplicando los dos y manda el más estricto.
            RateLimiter::for('api-global', function (Request $request) {
                return Limit::perMinute((int) config('rate_limits.api_global_per_minute'))
                    ->by(self::rateKey($request));
            });

            // Ruta de cierre: quien pide rutas que no existen está escaneando.
            // Por IP, que es lo único fiable ahí: un barrido no manda token.
            RateLimiter::for('api-fallback', function (Request $request) {
                return Limit::perMinute((int) config('rate_limits.api_fallback_per_minute'))
                    ->by('ip:'.$request->ip());
            });

            RateLimiter::for('api', function (Request $request) {
                return Limit::perMinute((int) config('rate_limits.api_per_minute'))
                    ->by(self::rateKey($request));
            });

            // Imágenes de páginas web, no llamadas de API: ver la nota de
            // config/rate_limits.php. Por IP, que es lo único que hay en una
            // ruta pública.
            RateLimiter::for('file-resize', function (Request $request) {
                return Limit::perMinute((int) config('rate_limits.file_resize_per_minute'))
                    ->by($request->ip());
            });

            RateLimiter::for('sensor-data', function (Request $request) {
                return Limit::perMinute((int) config('rate_limits.iot_store_per_minute'))
                    ->by(self::rateKey($request));
            });

            // Contacto: por IP, que es lo único que hay (el endpoint es público).
            RateLimiter::for('contact', function (Request $request) {
                return Limit::perHour((int) config('rate_limits.contact_per_hour'))->by($request->ip());
            });

            // Login: por IP y por email, para que intentar contra muchas cuentas
            // desde una IP no se salga por la puerta de al lado.
            RateLimiter::for('api-auth', function (Request $request) {
                $porMinuto = (int) config('rate_limits.auth_per_minute');

                return [
                    Limit::perMinute($porMinuto)->by('ip:'.$request->ip()),
                    Limit::perMinute($porMinuto)->by('email:'.strtolower((string) $request->input('email'))),
                ];
            });

            // Escrituras IoT: por TOKEN, que es lo que identifica al cacharro.
            RateLimiter::for('api-store', function (Request $request) {
                return Limit::perMinute((int) config('rate_limits.iot_store_per_minute'))
                    ->by(self::rateKey($request));
            });

            RateLimiter::for('api-store-batch', function (Request $request) {
                return Limit::perMinute((int) config('rate_limits.iot_batch_per_minute'))
                    ->by(self::rateKey($request));
            });
        }
    }

    /**
     * Clave de rate limit de las escrituras IoT: identifica al dispositivo, no
     * al dueño. Se usa el id del token, que es lo que hay dentro del cacharro.
     * Si la petición llega sin token (o por cookie de sesión), cae a la IP.
     */
    private static function rateKey(Request $request): string
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            return 'token:'.$token->getKey();
        }

        return 'ip:'.$request->ip();
    }
}
