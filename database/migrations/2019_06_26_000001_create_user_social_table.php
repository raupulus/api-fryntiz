<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateUserSocialTable
 */
class CreateUserSocialTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_social', function (Blueprint $table) {
            $table->comment('Almacena los registros correspondientes a user social para su integración y uso general en el sistema.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('user_id')->comment('Clave foránea que relaciona este registro con el user al que pertenece.');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade')->comment('Clave foránea que relaciona este registro con el user al que pertenece.');
            $table->unsignedBigInteger('social_network_id')->comment('Clave foránea que relaciona este registro con el social network al que pertenece.');
            $table->foreign('social_network_id')
                ->references('id')->on('social_networks')
                ->onUpdate('cascade')
                ->onDelete('cascade')->comment('Clave foránea que relaciona este registro con el social network al que pertenece.');
            $table->string('nick')
                ->index()
                ->nullable()
                ->comment('Nick o usuario dentro de la red social');
            $table->string('url')->comment('Enlace o URL');
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
        Schema::dropIfExists('user_social', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['social_networks']);
        });
    }
}
