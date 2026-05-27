<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateFilesTable
 */
class CreateFileThumbnailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_thumbnails', function (Blueprint $table) {
            $table->comment('Tabla para almacenar información de file thumbnails');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único');
            $table->unsignedBigInteger('file_id')
                ->nullable()
                ->comment('Imagen Asociada');
            $table->foreign('file_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('file_type_id')
                ->nullable()
                ->comment('FK al tipo de archivo');
            $table->foreign('file_type_id')
                ->references('id')->on('file_types')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->string('module', 255)
                ->nullable()
                ->comment('Nombre del módulo para acceder por path');

            $table->string('path', 511)
                ->nullable()
                ->comment('Ruta que tiene la aplicación hacia el archivo, por ejemplo: users/avatar');
            $table->string('storage_path', 511)
                ->nullable()
                ->comment('Ruta hacia el archivo en el storage');
            $table->string('name', 511)
                ->comment('Nombre asignado de forma interna en la aplicación, por ejemplo: fg7s97hg98hjsd8gh0d0.jpg');

            $table->string('key', 255)
                ->nullable()
                ->comment('Almacena la clave del tipo de thumbnail (small, medium...)');

            $table->integer('width')
                ->nullable()
                ->default(0)
                ->comment('Ancho de la imagen');

            $table->integer('height')
                ->nullable()
                ->default(0)
                ->comment('Alto de la imagen');

            $table->integer('size')
                ->default(0)
                ->comment('Tamaño de la imagen');

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
        Schema::dropIfExists('file_thumbnails', function (Blueprint $table) {
            $table->dropForeign(['file_id']);
            $table->dropForeign(['file_type_id']);
        });
    }
}
