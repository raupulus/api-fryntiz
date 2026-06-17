<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateGalleriesTable
 */
class CreateGalleriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('galleries', function (Blueprint $table) {
            $table->comment('Almacena agrupaciones de imágenes y multimedia compartidas a través de la plataforma.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('user_id')
                ->nullable()
                ->comment('Usuario que realiza la subida');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE')->comment('Clave foránea que relaciona este registro con el user al que pertenece.');
            $table->bigInteger('image_id')
                ->nullable()
                ->comment('Clave foránea que relaciona este registro con el image al que pertenece.');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('set null')->comment('Clave foránea que relaciona este registro con el image al que pertenece.');
            $table->string('name', 511)->comment('Nombre de la galería');
            $table->string('description', 1024)->comment('Descripción del contenido de la galería');
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
        Schema::dropIfExists('galleries', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['user_id']);
        });
    }
}
