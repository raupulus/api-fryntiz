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

    public const HARDWAREENERGY_READ = 'hardwareenergy:read';

    public const HARDWAREENERGY_WRITE = 'hardwareenergy:write';

    public const WEATHERSTATION_READ = 'weatherstation:read';

    public const WEATHERSTATION_WRITE = 'weatherstation:write';

    public const KEYCOUNTER_READ = 'keycounter:read';

    public const KEYCOUNTER_WRITE = 'keycounter:write';

    public const SMARTPLANT_READ = 'smartplant:read';

    public const SMARTPLANT_WRITE = 'smartplant:write';

    public const AIRFLIGHT_READ = 'airflight:read';

    public const AIRFLIGHT_WRITE = 'airflight:write';

    /**
     * Abilities de módulo que puede llevar un token de dispositivo, con su
     * etiqueta para el panel. No incluye `device:{id}`, que se añade sola.
     *
     * ⚠️ **Lectura y escritura son abilities distintas, y no se mezclan.**
     *
     * Hasta el 2026-09-02 sólo Hardware tenía `:read`, así que las rutas GET de
     * KeyCounter y SmartPlant se protegían con la ability de **escritura**: el
     * token que se graba en un teclado —cuyo único trabajo es hacer POST—
     * también podía listar todas las sesiones y todas las plantas de su dueño
     * (auditoría AR-S02). Eso es justo lo contrario de lo que el catálogo
     * pretende: «un token robado sólo puede hacer aquello para lo que se
     * emitió».
     *
     * A un dispositivo se le emite **sólo** la de escritura de su módulo. La de
     * lectura es para un panel o un cliente que consulte datos.
     *
     * **Hardware y Hardware Energy son módulos distintos** desde el 2026-09-06.
     * `hardware:*` es el aparato en sí —inventario y último estado conocido—, y
     * `hardwareenergy:*` son sus lecturas de energía, vengan de un controlador
     * solar o de una pinza de consumo. Hasta esa fecha las lecturas solares y
     * de energía iban con `hardware:write`, así que el token de un contador de
     * consumo también podía reescribir el estado del aparato: son cosas que se
     * conceden por separado.
     *
     * Ninguna ability de este catálogo está sin usar: cada una la exige alguna
     * ruta. Las que no protegían nada —las lecturas públicas de la estación
     * meteorológica y del radar de vuelos, que se sirven desde el bloque web—
     * dejaron de ser casillas que no hacían nada el 2026-09-06.
     *
     * @var array<string, string>
     */
    public const MODULE_ABILITIES = [
        self::HARDWARE_READ => 'Hardware lectura',
        self::HARDWARE_WRITE => 'Hardware escritura',
        self::HARDWAREENERGY_READ => 'Hardware Energy lectura',
        self::HARDWAREENERGY_WRITE => 'Hardware Energy escritura',
        self::WEATHERSTATION_READ => 'WeatherStation lectura',
        self::WEATHERSTATION_WRITE => 'WeatherStation escritura',
        self::KEYCOUNTER_READ => 'KeyCounter lectura',
        self::KEYCOUNTER_WRITE => 'KeyCounter escritura',
        self::SMARTPLANT_READ => 'SmartPlant lectura',
        self::SMARTPLANT_WRITE => 'SmartPlant escritura',
        self::AIRFLIGHT_READ => 'AirFlight lectura',
        self::AIRFLIGHT_WRITE => 'AirFlight escritura',
    ];

    /**
     * Qué abre cada ability, para que no haya que adivinarlo en el panel.
     *
     * El nombre del módulo no basta: «Hardware escritura» no dice si incluye
     * las lecturas de un controlador solar (no las incluye) y quien emite el
     * token acaba marcando de más por si acaso, que es justo lo contrario de
     * lo que persigue este catálogo.
     *
     * @var array<string, string>
     */
    public const MODULE_ABILITY_HINTS = [
        self::HARDWARE_READ => 'Listar tus dispositivos y ver su ficha.',
        self::HARDWARE_WRITE => 'Actualizar el último estado del dispositivo (IP, uptime, RAM, batería…).',
        self::HARDWAREENERGY_READ => 'Consultar las lecturas de energía y las del controlador solar.',
        self::HARDWAREENERGY_WRITE => 'Subir lecturas de energía y del controlador solar. Es la de un contador de consumo o unas placas.',
        self::WEATHERSTATION_READ => 'Consultar estaciones y el histórico de sus sensores.',
        self::WEATHERSTATION_WRITE => 'Subir lecturas de sensores de una estación.',
        self::KEYCOUNTER_READ => 'Consultar tus sesiones de teclado y ratón.',
        self::KEYCOUNTER_WRITE => 'Subir sesiones de teclado y ratón.',
        self::SMARTPLANT_READ => 'Consultar tus plantas y sus lecturas.',
        self::SMARTPLANT_WRITE => 'Subir lecturas de una planta.',
        self::AIRFLIGHT_READ => 'Consultar aviones detectados, incluido el historial por fechas.',
        self::AIRFLIGHT_WRITE => 'Registrar aviones detectados, de uno en uno o por lotes.',
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
     * Dispositivos a los que está ligado el token de la petición.
     *
     * Vacío cuando no hay token de dispositivo —una sesión humana— o cuando el
     * token no declara ninguno: ahí el límite lo pone la pertenencia, que se
     * comprueba aparte. Un listado que acote por dispositivo debe usar esto
     * para no enseñar el parque entero del dueño a un cacharro concreto.
     *
     * @return array<int, int>
     */
    public static function devicesReachableBy(?Authenticatable $user): array
    {
        $token = self::currentToken($user);

        return $token === null ? [] : self::devicesOf((array) $token->abilities);
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
