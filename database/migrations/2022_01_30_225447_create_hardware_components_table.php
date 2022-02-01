<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateHardwareComponentsTable
 *
 * Tabla asociando componentes concretos a los dispositivos.
 */
class CreateHardwareComponentsTable extends Migration
{
    private $tableName = 'hardware_components';
    private $tableComment = 'Tabla asociando componentes concretos a los dispositivos';

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
            $table->unsignedBigInteger('hardware_available_component_id')
                ->nullable()
                ->comment('Componente asociado al hardware');
            $table->foreign('hardware_available_component_id')
                ->references('id')->on('hardware_available_components')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo asociado');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->string('name')
                ->nullable()
                ->comment('Nombre del componente');
            $table->string('brand')
                ->nullable()
                ->comment('Marca del componente');
            $table->string('model')
                ->nullable()
                ->comment('Modelo del componente');
            $table->string('quantity')
                ->nullable()
                ->comment('Cantidad de unidades de este componente');
            $table->string('power')
                ->nullable()
                ->comment('Potencia, consumo en watios');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del componente');
            $table->timestamp('buy_at')
                ->nullable()
                ->comment('Fecha de adquisición, el momento de compra o inicio de posesión');

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
        Schema::dropIfExists($this->tableName, function (Blueprint $table) {
            $table->dropForeign(['hardware_available_component_id']);
        });
    }
}
