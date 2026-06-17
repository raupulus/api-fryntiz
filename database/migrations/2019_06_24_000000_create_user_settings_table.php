<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateUserSettingsTable
 */
class CreateUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_settings', function (Blueprint $table) {
            $table->comment('Almacena los registros correspondientes a user settings para su integración y uso general en el sistema.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('user_id')
                ->comment('Relación con el usuario');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade')->comment('Clave foránea que relaciona este registro con el user al que pertenece.');
            $table->boolean('send_email')
                ->nullable()
                ->default(true)
                ->comment('Indica si permite el envío de emails con información no prioritaria');
            $table->boolean('send_notification')
                ->nullable()
                ->default(true)
                ->comment('Indica si quiere notificaciones.');
            $table->boolean('send_notification_push')
                ->nullable()
                ->default(true)
                ->comment('Indica si permite notificaciones push');
            $table->timestamps()->comment('Marcas de tiempo utilizadas por Eloquent para llevar el registro de creación y última actualización.');
            $table->softDeletes()->comment('Marca de tiempo empleada por Eloquent para habilitar el borrado lógico (soft deletes).');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_settings', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }
}
