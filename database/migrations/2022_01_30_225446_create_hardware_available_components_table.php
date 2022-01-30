<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

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
            $table->timestamps();
            $table->softDeletes();
        });
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
