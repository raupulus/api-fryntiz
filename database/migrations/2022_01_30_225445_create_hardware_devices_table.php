<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateHardwareDevicesTable
 *
 * Tabla con los dispositivos de hardware
 */
class CreateHardwareDevicesTable extends Migration
{
    private $tableName = 'hardware_devices';
    private $tableComment = 'Dispositivos de hardware';

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
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario asociado');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

            $table->unsignedBigInteger('hardware_type_id')
                ->nullable()
                ->comment('Relación con el tipo de hardware asociado');
            $table->foreign('hardware_type_id')
                ->references('id')->on('hardware_types')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

            $table->unsignedBigInteger('referred_thing_id')
                ->nullable()
                ->comment('Relación con el dispositivo afiliado');
            $table->foreign('referred_thing_id')
                ->references('id')->on('referred_things')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');

            $table->string('name', 255)
                ->nullable()
                ->comment('Nombre real del dispositivo, EJ: Raspberry Pi 4b+');
            $table->string('name_friendly', 255)
                ->nullable()
                ->comment('Nombre amistoso para reconocerlo EJ: Raspberry en azotea');
            $table->string('ref', 255)
                ->nullable()
                ->comment('Referencia del dispositivo');
            $table->string('brand', 255)
                ->nullable()
                ->comment('Marca del fabricante, EJ: Apple');
            $table->string('model', 255)
                ->nullable()
                ->comment('Modelo del dispositivo, EJ: 4b+');
            $table->string('software_version', 255)
                ->nullable()
                ->comment('Versión del software del dispositivo');
            $table->string('hardware_version', 255)
                ->nullable()
                ->comment('Versión del hardware del dispositivo');
            $table->string('serial_number', 255)
                ->nullable()
                ->comment('Número de serie del dispositivo');
            $table->string('battery_type', 255)
                ->nullable()
                ->comment('Tipo de batería, EJ: Gel, li ion');
            $table->integer('battery_nominal_capacity')
                ->nullable()
                ->comment('Capacidad nominal de la batería en mAh, EJ: 4200');
            $table->string('url_company', 255)
                ->nullable()
                ->comment('Enlace a la página de la empresa fabricante');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del dispositivo.');
            $table->dateTime('buy_at')
                ->nullable()
                ->comment('Fecha de compra del dispositivo');

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
            $table->dropForeign(['user_id']);
            $table->dropForeign(['image_id']);
            $table->dropForeign(['hardware_type_id']);
            $table->dropForeign(['referred_thing_id']);
        });
    }
}
