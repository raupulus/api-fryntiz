<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateKeycounterKeyboardTable
 */
class CreateKeycounterKeyboardTable extends Migration
{
    private $tableName = 'keycounter_keyboard';
    private $tableComment = 'Pulsaciones de teclado';

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
            $table->bigInteger('pulsations')
                ->comment('Cantidad de pulsaciones total de la racha');
            $table->bigInteger('pulsations_special_keys')
                ->comment('Cantidad de pulsaciones para teclas especiales total de la racha');
            $table->decimal('pulsation_average', 14, 4)
                ->comment('Velocidad media de pulsaciones para la racha');
            $table->bigInteger('score')
                ->comment('Puntuación conseguida en esta racha');
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
