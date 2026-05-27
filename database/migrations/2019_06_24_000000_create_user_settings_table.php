<?php

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
            $table->comment('Tabla para almacenar información de user settings');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('user_id')
                ->comment('Relación con el usuario');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
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
            $table->timestamps()->comment('Marcas de tiempo de creación y actualización');
            $table->softDeletes()->comment('Marca de tiempo para borrado lógico');
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
