<?php

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
            $table->comment('Tabla para almacenar información de user social');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('social_network_id');
            $table->foreign('social_network_id')
                ->references('id')->on('social_networks')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->string('nick')
                ->index()
                ->nullable()
                ->comment('Nick o usuario dentro de la red social');
            $table->string('url')->comment('Enlace o URL');
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
        Schema::dropIfExists('user_social', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['social_networks']);
        });
    }
}
