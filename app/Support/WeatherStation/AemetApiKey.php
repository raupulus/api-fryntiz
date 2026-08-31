<?php

declare(strict_types=1);

namespace App\Support\WeatherStation;

use Illuminate\Support\Carbon;
use Throwable;

/**
 * El estado de la clave de AEMET.
 *
 * La clave es un JWT que **caduca a los ~100 días**. Cuando caduca, AEMET no
 * responde un 401: responde **200 con el cuerpo vacío**. Es decir, una
 * integración que no vigile esto se queda muda a los tres meses de desplegarla y
 * lo único que se ve en los logs es «payload vacío», que es lo mismo que sale
 * cuando de verdad no hay datos.
 *
 * Por eso el aviso va **por delante** de la caducidad y no cuando ya ha pasado.
 *
 * `AEMET_API_KEY_EXPIRES_AT` se apunta a mano al renovar la clave, porque el
 * JWT trae su propio `exp` pero no hay garantía de que AEMET lo respete: si el
 * token es legible se usa como respaldo, y si no, manda lo configurado.
 */
final class AemetApiKey
{
    public const OK = 'ok';

    public const EXPIRING = 'expiring';

    public const EXPIRED = 'expired';

    public const NO_EXPIRY_DATE = 'no_expiry_date';

    public const NO_KEY = 'no_key';

    /**
     * Estado, fecha de caducidad y días que quedan.
     *
     * @return array{status:string, expires_on:?Carbon, days:?int, message:string}
     */
    public static function status(): array
    {
        if ((string) config('aemet.api_key', '') === '') {
            return [
                'status' => self::NO_KEY,
                'expires_on' => null,
                'days' => null,
                'message' => 'No hay AEMET_API_KEY configurada: AEMET responde 200 con el cuerpo vacío y parece que no hay datos.',
            ];
        }

        $expires = self::expiresOn();

        if ($expires === null) {
            return [
                'status' => self::NO_EXPIRY_DATE,
                'expires_on' => null,
                'days' => null,
                'message' => 'No se sabe cuándo caduca la clave. Apunta AEMET_API_KEY_EXPIRES_AT en el .env al renovarla: '.
                    'una clave caducada no da error, deja de traer datos.',
            ];
        }

        $days = (int) now()->startOfDay()->diffInDays($expires->copy()->startOfDay(), false);
        $margen = (int) config('aemet.warn_days_before_expiry', 15);

        if ($days < 0) {
            return [
                'status' => self::EXPIRED,
                'expires_on' => $expires,
                'days' => $days,
                'message' => sprintf(
                    'La clave de AEMET caducó hace %d días (%s). Renuévala en %s: mientras tanto AEMET responde 200 con el cuerpo vacío.',
                    abs($days),
                    $expires->format('d/m/Y'),
                    self::RENEWAL_URL
                ),
            ];
        }

        if ($days <= $margen) {
            return [
                'status' => self::EXPIRING,
                'expires_on' => $expires,
                'days' => $days,
                'message' => sprintf(
                    'La clave de AEMET caduca en %d días (%s). Renuévala en %s antes de que deje de traer datos sin avisar.',
                    $days,
                    $expires->format('d/m/Y'),
                    self::RENEWAL_URL
                ),
            ];
        }

        return [
            'status' => self::OK,
            'expires_on' => $expires,
            'days' => $days,
            'message' => sprintf('La clave de AEMET caduca el %s, dentro de %d días.', $expires->format('d/m/Y'), $days),
        ];
    }

    public const RENEWAL_URL = 'https://opendata.aemet.es/centrodedescargas/altaUsuario';

    /**
     * ¿Hay que hacer algo con la clave?
     */
    public static function needsRenewal(): bool
    {
        return in_array(
            self::status()['status'],
            [self::EXPIRED, self::EXPIRING, self::NO_KEY],
            true
        );
    }

    /**
     * Cuándo caduca: lo configurado, y si no, el `exp` del propio JWT.
     */
    public static function expiresOn(): ?Carbon
    {
        $isConfigured = config('aemet.expires_at');

        if (is_string($isConfigured) && trim($isConfigured) !== '') {
            try {
                return Carbon::parse($isConfigured);
            } catch (Throwable) {
                // Una fecha mal escrita en el .env no puede tumbar un comando;
                // se cae al `exp` del token.
            }
        }

        return self::tokenExpiry((string) config('aemet.api_key', ''));
    }

    /**
     * `exp` del JWT, si la clave lo es y se puede leer.
     *
     * No se valida la firma —no tenemos el secreto ni falta— y por eso esto es
     * sólo un respaldo de lo que se apunte a mano.
     */
    private static function tokenExpiry(string $key): ?Carbon
    {
        $parts = explode('.', $key);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = base64_decode(strtr($parts[1], '-_', '+/'), true);

        if ($payload === false) {
            return null;
        }

        $data = json_decode($payload, true);

        if (! is_array($data) || ! isset($data['exp']) || ! is_numeric($data['exp'])) {
            return null;
        }

        return Carbon::createFromTimestampUTC((int) $data['exp']);
    }
}
