<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `role` como única fuente de verdad, y una fila por papel de verdad.
 *
 * Cuatro cosas, y las cuatro venían de que la tabla se montó con dos formas de
 * decir lo mismo:
 *
 * 1. **`storage` pasa a llamarse `battery`.** Es lo que es y lo que ya ponía la
 *    etiqueta del panel; el valor guardado decía otra cosa.
 *
 * 2. **Fuera `is_generator`.** Duplicaba a `role` y encima no podía expresar el
 *    tercer papel: una batería no es generadora ni consumidora, y la booleana
 *    la dejaba en `false`, o sea indistinguible de una carga. Dos columnas para
 *    lo mismo acaban discrepando, y aquí ya lo habían hecho.
 *
 * 3. **Fuera `name`.** Un campo más que rellenar a mano para escribir lo que ya
 *    se sabe: el elemento es «tal dispositivo haciendo tal papel», y las dos
 *    cosas están en la fila. `HardwareEnergy::display_name` lo compone.
 *
 * 4. **Índice único** sobre dispositivo, monitorizado, papel y canal. Sin él se
 *    podían crear cuatro generadores del mismo aparato, y nada avisaba.
 *
 * `sensor_position` pasa a `NOT NULL` con `0` por defecto **porque el índice lo
 * necesita**: en PostgreSQL dos `NULL` no chocan entre sí, así que con la
 * columna nullable dos filas idénticas con el canal vacío pasarían la
 * restricción tan ricamente y ésta no serviría para nada.
 */
return new class extends Migration
{
    private string $tableName = 'hardware_energy';

    private string $indexName = 'hardware_energy_device_monitorized_role_position_unique';

    public function up(): void
    {
        // 1. El tercer papel, con su nombre.
        DB::table($this->tableName)
            ->where('role', 'storage')
            ->update(['role' => 'battery']);

        // 2. El canal, sin nulos: es parte de la clave de abajo.
        DB::table($this->tableName)
            ->whereNull('sensor_position')
            ->update(['sensor_position' => 0]);

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->smallInteger('sensor_position')
                ->default(0)
                ->nullable(false)
                ->comment('Qué sensor del medidor corresponde a este elemento. 0 si sólo tiene uno.')
                ->change();

            $table->string('role', 16)
                ->default('load')
                ->comment('generator | load | battery.')
                ->change();
        });

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropColumn(['is_generator', 'name']);
        });

        Schema::table($this->tableName, function (Blueprint $table) {
            $table->unique(
                ['hardware_device_id', 'hardware_device_monitorized_id', 'role', 'sensor_position'],
                $this->indexName,
            );
        });
    }

    public function down(): void
    {
        Schema::table($this->tableName, function (Blueprint $table) {
            $table->dropUnique($this->indexName);

            $table->boolean('is_generator')
                ->default(false)
                ->nullable()
                ->comment('true = genera energía; false = la consume. Lo detalla `role`.');

            $table->string('name', 255)
                ->nullable()
                ->comment('«Panel sur», «Router principal».');

            $table->smallInteger('sensor_position')
                ->nullable()
                ->comment('Qué sensor del medidor corresponde a este elemento, cuando tiene varios.')
                ->change();
        });

        // La booleana se reconstruye del papel, que es de donde debió salir.
        DB::table($this->tableName)
            ->where('role', 'generator')
            ->update(['is_generator' => true]);

        DB::table($this->tableName)
            ->where('role', 'battery')
            ->update(['role' => 'storage']);
    }
};
