<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateGalleryImagesTable
 */
class CreateGalleryImagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gallery_images', function (Blueprint $table) {
            $table->comment('Almacena agrupaciones de imágenes y multimedia compartidas a través de la plataforma.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->bigInteger('gallery_id')
                ->nullable()
                ->comment('Clave foránea que relaciona este registro con el gallery al que pertenece.');
            $table->foreign('gallery_id')
                ->references('id')->on('galleries')
                ->onUpdate('cascade')
                ->onDelete('cascade')->comment('Clave foránea que relaciona este registro con el gallery al que pertenece.');
            $table->bigInteger('image_id')
                ->nullable()
                ->comment('Clave foránea que relaciona este registro con el image al que pertenece.');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('cascade')->comment('Clave foránea que relaciona este registro con el image al que pertenece.');
            $table->timestamps()->comment('Marcas de tiempo utilizadas por Eloquent para llevar el registro de creación y última actualización.');
            $table->softDeletes()->comment('Marca de tiempo empleada por Eloquent para habilitar el borrado lógico (soft deletes).');

            $table->unique(['gallery_id', 'image_id']);
            $table->index(['gallery_id', 'image_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('gallery_images', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['gallery_id']);
        });
    }
}
