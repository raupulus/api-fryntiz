<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateHardwareAvailableComponentsTable
 */
class CreateHardwareAvailableComponentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hardware_available_components', function (Blueprint $table) {
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
        Schema::dropIfExists('hardware_available_components', function (Blueprint $table) {
            $table->dropForeign(['hardware_device_id']);
        });
    }
}
