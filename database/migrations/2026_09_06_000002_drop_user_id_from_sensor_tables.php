<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Retira `user_id` de las tablas de sensores.
 *
 * El dueño de una lectura es el dueño de la estación que la tomó, y eso ya está
 * a un salto: `hardware_device_id` → `hardware_devices.user_id`. Guardarlo
 * además en cada fila es duplicar el mismo dato millones de veces —sólo
 * `meteorology_humidity` pasa de los tres millones— y abre la puerta a que las
 * dos copias digan cosas distintas.
 *
 * La ingesta de V2 ya no lo rellenaba, así que todo lo que ha entrado desde el
 * despliegue tiene `user_id` a null: la columna no sólo sobra, es que ya estaba
 * a medio abandonar.
 *
 * ## Comprobado antes de escribir esto
 *
 * Sobre los datos reales de producción, en las trece tablas:
 *
 *  · **Cero** filas con un `user_id` distinto al del dispositivo. La columna es
 *    redundante donde hay dispositivo, no contradictoria.
 *  · **Ocho** filas —3 en `meteorology_humidity`, 5 en `meteorology_pressure`,
 *    todas del 2024-11-05 y del usuario 2— tienen `user_id` sin
 *    `hardware_device_id`. Son las únicas que pierden la referencia al dueño.
 *
 * Ninguna consulta del proyecto filtra por `user_id` en estas tablas: los
 * `whereUserId()` que aparecen en los modelos son anotaciones PHPDoc generadas,
 * no uso real.
 *
 * ## Reversible sólo a medias
 *
 * El `down()` devuelve la columna y la rellena desde el dispositivo, que
 * reconstruye el 100 % de las filas que tenían dispositivo. Las ocho huérfanas
 * no se pueden recuperar: su dueño sólo estaba en la columna que se borra. Se
 * dejan anotadas aquí por si algún día importan.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tablas = [
        'meteorology_air_quality',
        'meteorology_eco2',
        'meteorology_humidity',
        'meteorology_light',
        'meteorology_lightning',
        'meteorology_pressure',
        'meteorology_rain',
        'meteorology_resume_historical',
        'meteorology_resume_today',
        'meteorology_temperature',
        'meteorology_tvoc',
        'meteorology_wind_direction',
        'meteorology_winter',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            if (! Schema::hasColumn($tabla, 'user_id')) {
                continue;
            }

            // Sólo `dropColumn`: en PostgreSQL, quitar una columna se lleva por
            // delante las claves foráneas y los índices que dependan de ella.
            // Soltarlas a mano antes obligaría a acertar el nombre que le puso
            // cada una de las trece migraciones originales, y `dropForeign()`
            // con un array compone el nombre a partir de la tabla, así que
            // pasarle uno ya completo lo duplica
            // («meteorology_air_quality_meteorology_air_quality_user_id_foreign_foreign»).
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            if (Schema::hasColumn($tabla, 'user_id')) {
                continue;
            }

            Schema::table($tabla, function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')
                    ->nullable()
                    ->after('id')
                    ->comment('Usuario dueño del registro. Redundante: sale del dispositivo.');
            });

            // Se reconstruye desde el dispositivo, que es de donde salía. Las
            // filas sin dispositivo se quedan a null: su dueño vivía sólo en la
            // columna que se borró.
            DB::statement("
                UPDATE {$tabla} t
                SET user_id = d.user_id
                FROM hardware_devices d
                WHERE d.id = t.hardware_device_id
            ");
        }
    }
};
