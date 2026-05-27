<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwareAvailableComponentsTable
 *
 * Tabla con los tipos de components disponibles para un hardware. Esta tabla
 * almacenará los tipos de componentes que puede tener un hardware como son
 * tarjeta gráfica, procesador, memoria ram... A grandes rasgos sin entrar en
 * marcas/modelos o especificaciones (esto se hace en la tabla hardware_components)
 */
class CreateHardwareAvailableComponentsTable extends Migration
{
    private $tableName = 'hardware_available_components';

    private $tableComment = 'Tipos de componentes disponibles para un hardware';

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
            $table->string('name', 255)
                ->comment('Nombre general (Tarjeta gráfica, Procesador...)');
            $table->string('type', 255)
                ->nullable()
                ->comment('Tipo de componente (gpu, cpu, ram..)');
            $table->string('slug', 255)
                ->comment('Slug para el tipo');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del componente');
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
        Schema::dropIfExists($this->tableName, function (Blueprint $table) {
            $table->dropForeign(['hardware_device_id']);
        });
    }
}
