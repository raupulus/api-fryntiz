<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateKeycounterMouseTable
 */
class CreateKeycounterMouseTable extends Migration
{
    private $tableName = 'keycounter_mouse';
    private $tableComment = 'Pulsaciones de ratón';

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
            $table->unsignedBigInteger('hardware_device_id')
                ->nullable()
                ->comment('Dispositivo asociado');
            $table->foreign('hardware_device_id')
                ->references('id')->on('hardware_devices')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
            $table->timestamp('start_at')
                ->comment('Momento de iniciar la racha');
            $table->timestamp('end_at')
                ->comment('Momento del final de la racha');
            $table->bigInteger('duration')
                ->comment('Duración en Segundos de la racha');
            $table->bigInteger('clicks_left')
                ->comment('Cantidad de clicks derecho');
            $table->bigInteger('clicks_right')
                ->comment('Cantidad de clicks izquierdo');
            $table->bigInteger('clicks_middle')
                ->comment('Cantidad de clicks centrales');
            $table->bigInteger('total_clicks')
                ->comment('Cantidad de clicks total de la racha');
            $table->decimal('clicks_average', 14, 4)
                ->comment('Cantidad de cliks medio de la racha');
            $table->bigInteger('weekday')
                ->comment('Día de la semana (0 es domingo)');
            $table->timestamps();
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
            $table->dropForeign(['hardware_device_id']);
        });
    }
}
