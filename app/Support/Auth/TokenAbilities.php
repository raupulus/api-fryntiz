<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\Hardware\HardwareDevice;
use Illuminate\Contracts\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Catálogo único de abilities (scopes) de los tokens Sanctum de la API V2.
 *
 * Los tokens no caducan (los cacharros están en sitios a los que no se sube a
 * reflashear un token). La seguridad la da el alcance: un token robado sólo
 * puede hacer aquello para lo que se emitió.
 *
 * Hay dos familias que **nunca** se mezclan:
 *
 *  - {@see self::SESSION}: token de sesión humana. Lo emite el login y sólo
 *    sirve para leer el propio usuario y cerrar sesión. Ningún dispositivo lo
 *    lleva jamás.
 *  - Abilities de módulo (`hardware:write`, `weatherstation:write`…): las llevan
 *    los tokens de dispositivo, junto con `device:{id}` que los liga a un
 *    cacharro concreto. Ninguna de ellas alcanza rutas de cuenta.
 *
 * El comodín `*` no se emite nunca desde código de la aplicación.
 */
final class TokenAbilities
{
    /**
     * Sesión humana: obtenida en /auth/login. No la lleva ningún dispositivo.
     */
    public const SESSION = 'session';

    /**
     * Prefijo de la ability que liga un token a un dispositivo concreto.
     */
    public const DEVICE_PREFIX = 'device:';

    public const HARDWARE_READ = 'hardware:read';

    public const HARDWARE_WRITE = 'hardware:write';

    public const WEATHERSTATION_WRITE = 'weatherstation:write';

    public const KEYCOUNTER_WRITE = 'keycounter:write';

    public const SMARTPLANT_WRITE = 'smartplant:write';

    public const AIRFLIGHT_WRITE = 'airflight:write';

    /**
     * Abilities de módulo que puede llevar un token de dispositivo, con su
     * etiqueta para el panel. No incluye `device:{id}`, que se añade sola.
     *
     * @var array<string, string>
     */
    public const MODULE_ABILITIES = [
        self::HARDWARE_READ => 'Hardware lectura',
        self::HARDWARE_WRITE => 'Hardware escritura',
        self::WEATHERSTATION_WRITE => 'WeatherStation escritura',
        self::KEYCOUNTER_WRITE => 'KeyCounter escritura',
        self::SMARTPLANT_WRITE => 'SmartPlant escritura',
        self::AIRFLIGHT_WRITE => 'AirFlight escritura',
    ];

    /**
     * Abilities que emite el login de la API para una persona.
     *
     * @return array<int, string>
     */
    public static function forSession(): array
    {
        return [self::SESSION];
    }

    /**
     * Ability que liga un token a un dispositivo concreto.
     */
    public static function forDevice(HardwareDevice|int $device): string
    {
        return self::DEVICE_PREFIX.($device instanceof HardwareDevice ? $device->id : $device);
    }

    /**
     * Extrae los ids de dispositivo declarados en un juego de abilities.
     *
     * @param  array<int, mixed>  $abilities
     * @return array<int, int>
     */
    public static function devicesOf(array $abilities): array
    {
        $ids = [];

        foreach ($abilities as $ability) {
            if (is_string($ability) && str_starts_with($ability, self::DEVICE_PREFIX)) {
                $ids[] = (int) substr($ability, strlen(self::DEVICE_PREFIX));
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Comprueba si un juego de abilities es de módulo (token de dispositivo).
     *
     * @param  array<int, mixed>  $abilities
     */
    public static function isDeviceToken(array $abilities): bool
    {
        foreach ($abilities as $ability) {
            if (is_string($ability) && isset(self::MODULE_ABILITIES[$ability])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Token Sanctum real de la petición en curso, o null.
     *
     * Devuelve null cuando se autentica por cookie de sesión (`statefulApi()`
     * entrega un `TransientToken`, que no tiene abilities propias) o cuando no
     * hay usuario.
     */
    public static function currentToken(?Authenticatable $user): ?PersonalAccessToken
    {
        if (! $user || ! method_exists($user, 'currentAccessToken')) {
            return null;
        }

        $token = $user->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token : null;
    }

    /**
     * ¿La petición viene de un token de dispositivo IoT?
     *
     * Un token de dispositivo es el que declara `device:{id}` o alguna ability
     * de módulo. Nunca lleva {@see self::SESSION}.
     */
    public static function deviceRequest(?Authenticatable $user): bool
    {
        $token = self::currentToken($user);

        if (! $token) {
            return false;
        }

        $abilities = (array) ($token->abilities ?? []);

        return self::devicesOf($abilities) !== [] || self::isDeviceToken($abilities);
    }

    /**
     * ¿El token de la petición puede tocar este dispositivo concreto?
     *
     * Si el token declara dispositivos (`device:{id}`), sólo alcanza esos. Un
     * token que no declara ninguno —una sesión humana— no queda restringido
     * aquí: su límite es la pertenencia del dispositivo, que se comprueba
     * aparte.
     */
    public static function tokenReachesDevice(?Authenticatable $user, int $deviceId): bool
    {
        $token = self::currentToken($user);

        if (! $token) {
            return true;
        }

        $declared = self::devicesOf((array) ($token->abilities ?? []));

        return $declared === [] || in_array($deviceId, $declared, true);
    }
}
