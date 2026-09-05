<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
