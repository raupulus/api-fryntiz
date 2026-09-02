<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Planificador
|--------------------------------------------------------------------------
|
| De las seis tareas que había aquí, CUATRO llamaban a comandos que no
| existen: `aemet:adverse-events`, `aemet:contamination`, `aemet:predictions`
| y `keycounter:maintenance`. El planificador de Laravel no avisa de eso: la
| tarea se ejecuta, artisan no encuentra el comando y todo sigue en silencio.
| Junto con el fallo de descodificación de AEMET, es la razón por la que ese
| módulo lleva sin actualizarse desde siempre.
|
| Reglas que se aplican a todo lo de abajo:
|
|  - `withoutOverlapping()`: si una ejecución se alarga, la siguiente no
|    arranca encima. Sin esto, dos procesos pidiendo el mismo endpoint de
|    AEMET gastan el doble de cuota (40 peticiones por endpoint, por IP).
|  - `runInBackground()` en lo que sale a la red, para que una tarea lenta no
|    retrase a las demás del mismo minuto.
|  - `onFailure()`: deja constancia. Antes un fallo no se veía en ningún sitio.
|
| Las horas son las del huso de la aplicación (`config('app.timezone')`, UTC).
| `->timezone('Europe/Madrid')` fija las que tienen que caer a una hora local
| concreta, para que no se muevan con el cambio de hora.
*/

/**
 * Deja constancia de una tarea que falla, con su nombre.
 */
$warnOnFailure = static function (string $tarea): callable {
    return static function () use ($tarea): void {
        Log::error("Planificador: la tarea «{$tarea}» ha terminado con error.");
    };
};

// ── Contenido ────────────────────────────────────────────────────────────────

Schedule::command('content:publish')
    ->hourly()
    ->withoutOverlapping()
    ->onFailure($warnOnFailure('content:publish'));

Schedule::command('sitemap:generate')
    ->dailyAt('04:30')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping()
    ->onFailure($warnOnFailure('sitemap:generate'));

// ── AEMET ────────────────────────────────────────────────────────────────────
//
// La cadencia de cada uno sale de la `periodicidad` que declara AEMET para su
// producto, no de un número inventado. Pedir más a menudo no trae datos nuevos
// y sí gasta la cuota del endpoint.

// Avisos de fenómenos adversos: es lo único que justifica media hora.
Schedule::command('aemet:adverse-events')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure($warnOnFailure('aemet:adverse-events'));

// Contaminación: AEMET la publica cada hora.
Schedule::command('aemet:contamination')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure($warnOnFailure('aemet:contamination'));

// Predicción horaria: AEMET la rehace cada 3 h.
Schedule::command('aemet:hourly-prediction')
    ->everyFourHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure($warnOnFailure('aemet:hourly-prediction'));

// Costa: dos emisiones al día, mediodía y tarde.
Schedule::command('aemet:coast')
    ->twiceDailyAt(12, 20, 10)
    ->timezone('Europe/Madrid')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure($warnOnFailure('aemet:coast'));

// Diarios de la mañana, separados unos minutos para no pedir todo a la vez.
Schedule::command('aemet:beaches')
    ->dailyAt('08:05')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure($warnOnFailure('aemet:beaches'));

Schedule::command('aemet:high-sea')
    ->dailyAt('08:15')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure($warnOnFailure('aemet:high-sea'));

Schedule::command('aemet:sun-radiation')
    ->dailyAt('08:25')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure($warnOnFailure('aemet:sun-radiation'));

Schedule::command('aemet:ozone')
    ->dailyAt('12:25')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping()
    ->runInBackground()
    ->onFailure($warnOnFailure('aemet:ozone'));

// La clave de AEMET es un JWT que caduca a los ~100 días, y cuando caduca la API
// responde 200 con el cuerpo VACÍO en vez de un 401: en los logs es idéntico a
// «hoy no hay datos». Sin esta comprobación la integración se queda muda y no se
// entera nadie. Se mira una vez al día, y avisa 15 días antes.
Schedule::command('aemet:check-api-key')
    ->dailyAt('08:00')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping()
    ->onFailure($warnOnFailure('aemet:check-api-key'));

// ── KeyCounter ───────────────────────────────────────────────────────────────
//
// `keycounter:maintenance` no existe. Los comandos reales son estos dos.

Schedule::command('keycounter:remove_duplicate')
    ->weeklyOn(1, '03:00')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping()
    ->onFailure($warnOnFailure('keycounter:remove_duplicate'));

Schedule::command('keycounter:generate_duration')
    ->weeklyOn(1, '03:30')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping()
    ->onFailure($warnOnFailure('keycounter:generate_duration'));

// ── Dispositivos ─────────────────────────────────────────────────────────────

// Nada avisaba cuando un cacharro dejaba de reportar. En producción el monitor
// del Rover llevaba parado y no se enteró nadie.
// Sin `onFailure`: este comando sale con código 1 cuando ENCUENTRA algo, que es
// su trabajo, no un fallo suyo. Él mismo deja el aviso en el log con el detalle.
Schedule::command('iot:check-silent-devices', ['--hours=24'])
    ->dailyAt('09:00')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping();

// ── Currículums ──────────────────────────────────────────────────────────────

// Red de seguridad de B5: lo normal es pulsar «Regenerar PDF» al terminar de
// editar, pero eso se olvida. Esto recoge lo que quedó marcado.
Schedule::command('cv:regenerate-pdfs')
    ->dailyAt('03:45')
    ->timezone('Europe/Madrid')
    ->withoutOverlapping()
    ->onFailure($warnOnFailure('cv:regenerate-pdfs'));

// ── Colas ────────────────────────────────────────────────────────────────────

// Los jobs que han agotado sus reintentos se guardan para siempre en
// `failed_jobs`. Se conservan dos semanas, que es tiempo de sobra para
// mirarlos, y se limpian solos.
Schedule::command('queue:prune-failed', ['--hours=336'])
    ->daily()
    ->onFailure($warnOnFailure('queue:prune-failed'));

// ── Tokens ───────────────────────────────────────────────────────────────────

// Tokens de sesión caducados.
//
// Los de dispositivo NO caducan a propósito —los cacharros están en sitios a
// los que no se sube a reflashear un token—, así que este comando sólo se lleva
// los que tienen `expires_at` pasado: las sesiones humanas, que viven 30 días
// (`API_SESSION_DAYS`). Sin esto sus filas se quedaban para siempre en
// `personal_access_tokens` y `GET /auth/tokens` las paginaba (auditoría AR-R02).
Schedule::command('sanctum:prune-expired', ['--hours=720'])
    ->daily()
    ->onFailure($warnOnFailure('sanctum:prune-expired'));
