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

    private $tableComment = 'Tabla para almacenar los tipos de hardware, EJ: ordenador, portatil, microcontrolador, cargador solar....';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint $table) {
            $table->comment('Tabla para almacenar información de $la tabla');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->string('name', '255')
                ->unique()
                ->comment('Nombre del tipo de hardware (EJ: Portátil).');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del tipo de hardware.');

            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
            $table->softDeletes()->comment('Marca de tiempo para borrado lógico');
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
