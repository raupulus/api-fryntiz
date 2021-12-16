<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateFilesTable
 */
class CreateFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
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
                ->comment('Ruta que tiene la aplicación hacia el archivo, por ejemplo: users/avatar');
            $table->string('storage_path', 511)
                ->nullable()
                ->comment('Ruta hacia el archivo en el storage');
            $table->string('name', 511)
                ->comment('Nombre asignado de forma interna en la aplicación, por ejemplo: fg7s97hg98hjsd8gh0d0.jpg');

            $table->integer('width')
                ->nullable()
                ->default(0)
                ->comment('Ancho de la imagen');

            $table->integer('height')
                ->nullable()
                ->default(0)
                ->comment('Alto de la imagen');

            $table->string('original_name', 511)
                ->nullable()
                ->comment('Nombre original del archivo, el nombre que lleva al subirse');
            $table->integer('size')
                ->default(0)
                ->comment('Tamaño de la imagen');
            $table->string('alt', 511);
            $table->string('title', 511);
            $table->boolean('is_private')
                ->default(0)
                ->comment('Indica si es privado el archivo o pertenece al espacio público de la aplicación');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['file_type_id']);
        });
    }
}
