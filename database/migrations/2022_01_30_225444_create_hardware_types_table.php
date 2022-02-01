<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->string('name', '255')
                ->unique()
                ->comment('Nombre del tipo de hardware (EJ: Portátil).');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del tipo de hardware.');

            $table->timestamps();
            $table->softDeletes();
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
