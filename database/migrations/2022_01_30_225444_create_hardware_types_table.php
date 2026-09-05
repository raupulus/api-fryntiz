<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Class CreateReferredThingsTable
 *
 * Tabla para almacenar las cosas referidas de programas de afiliación.
 */
class CreateHardwareTypesTable extends Migration
{
    private $tableName = 'hardware_types';

    private $tableComment = 'Tipos de hardware: ordenador, portátil, microcontrolador, cargador solar…';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Tipos de hardware: ordenador, portátil, microcontrolador, cargador solar…');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->string('name', '255')
                ->unique()
                ->comment('Nombre del tipo de hardware (EJ: Portátil).');
            $table->string('slug', 80)
                ->nullable()
                ->unique()
                ->comment('Identificador estable para la API, sin acentos ni espacios');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del tipo de hardware.');

            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
        });

        // Relleno para cuando la tabla se monta sobre datos que ya existían: la
        // columna `slug` se añadió después que la tabla, y su migración
        // rellenaba lo que hubiera. Al plegarla aquí, ese paso se perdió y los
        // once tipos se quedaron con `slug` a null — que es el identificador
        // con el que la API los busca.
        $sinSlug = DB::table($this->tableName)->whereNull('slug')->get(['id', 'name']);

        foreach ($sinSlug as $tipo) {
            DB::table($this->tableName)
                ->where('id', $tipo->id)
                ->update(['slug' => Str::slug((string) $tipo->name)]);
        }

        DB::statement("COMMENT ON TABLE {$this->tableName} IS '{$this->tableComment}'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists($this->tableName);
    }
}
