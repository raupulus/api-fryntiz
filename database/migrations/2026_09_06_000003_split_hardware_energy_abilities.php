<?php

declare(strict_types=1);

use App\Support\Auth\TokenAbilities;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reparte las abilities de energía a los tokens que ya están en la calle.
 *
 * Las lecturas solares y de energía se cobraban con `hardware:write` y pasan a
 * exigir `hardwareenergy:write`. Los tokens ya emitidos —los que llevan grabados
 * los cacharros— no se enteran de ese cambio: al desplegar, un controlador solar
 * que hasta ese momento subía correctamente empezaría a comerse un 403 en cada
 * envío, sin que nadie lo mire hasta que se echen en falta los datos.
 *
 * Así que el permiso nuevo se les añade aquí, en el mismo despliegue que lo
 * exige. Es **aditivo**: no se le quita nada a ningún token, sólo se le concede
 * sobre las rutas de energía lo mismo que ya podía hacer ayer. Lo mismo con la
 * lectura.
 *
 * Reemitir los tokens a mano habría sido lo «limpio», y también subir a la
 * azotea a reflashear cada cacharro.
 */
return new class extends Migration
{
    /**
     * Ability de partida y la que se le añade.
     */
    private const REPARTO = [
        TokenAbilities::HARDWARE_WRITE => TokenAbilities::HARDWAREENERGY_WRITE,
        TokenAbilities::HARDWARE_READ => TokenAbilities::HARDWAREENERGY_READ,
    ];

    public function up(): void
    {
        $this->reescribirAbilities(function (array $abilities): array {
            foreach (self::REPARTO as $origen => $nueva) {
                if (in_array($origen, $abilities, true) && ! in_array($nueva, $abilities, true)) {
                    $abilities[] = $nueva;
                }
            }

            return $abilities;
        });
    }

    /**
     * Retira las abilities nuevas y deja los tokens como estaban.
     *
     * Un token que tuviera `hardwareenergy:*` sin la de hardware correspondiente
     * no lo emitió esta migración, así que se respeta.
     */
    public function down(): void
    {
        $this->reescribirAbilities(function (array $abilities): array {
            foreach (self::REPARTO as $origen => $nueva) {
                if (in_array($origen, $abilities, true)) {
                    $abilities = array_values(array_filter(
                        $abilities,
                        static fn (string $ability): bool => $ability !== $nueva
                    ));
                }
            }

            return $abilities;
        });
    }

    /**
     * Aplica una transformación a las abilities de cada token, fila a fila.
     *
     * `abilities` es una columna de texto con JSON dentro, no un array de
     * PostgreSQL, así que no hay operador que haga esto en una sola sentencia.
     * Son decenas de filas: el bucle no es el problema.
     *
     * @param  callable(array<int, string>): array<int, string>  $transformar
     */
    private function reescribirAbilities(callable $transformar): void
    {
        DB::table('personal_access_tokens')
            ->select(['id', 'abilities'])
            ->orderBy('id')
            ->chunk(200, function ($tokens) use ($transformar) {
                foreach ($tokens as $token) {
                    $abilities = json_decode((string) $token->abilities, true);

                    if (! is_array($abilities)) {
                        continue;
                    }

                    /** @var array<int, string> $actuales */
                    $actuales = array_values(array_filter($abilities, 'is_string'));
                    $nuevas = $transformar($actuales);

                    if ($nuevas === $actuales) {
                        continue;
                    }

                    DB::table('personal_access_tokens')
                        ->where('id', $token->id)
                        ->update(['abilities' => json_encode(array_values($nuevas))]);
                }
            });
    }
};
