<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentsTable
 */
class CreateContentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contents', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->bigInteger('user_id')
                ->index()
                ->nullable()
                ->comment('FK al usuario propietario del post');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->bigInteger('section_id')
                ->index()
                ->nullable()
                ->comment('FK a la sección que se asocia este contenido');
            $table->foreign('section_id')
                ->references('id')->on('sections')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->bigInteger('status_id')
                ->index()
                ->nullable()
                ->comment('FK al estado en la tabla content_status');
            $table->foreign('status_id')
                ->references('id')->on('content_status')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->bigInteger('type_id')
                ->index()
                ->nullable()
                ->comment('FK al tipo de contenido en la tabla content_type');
            $table->foreign('type_id')
                ->references('id')->on('content_types')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->bigInteger('file_id')
                ->nullable()
                ->comment('FK a la imagen en la tabla files');
            $table->foreign('file_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->string('title', 511)
                ->index()
                ->comment('Título de la página');
            $table->string('slug', 255)
                ->index()
                ->unique()
                ->comment('Slug para el URL');
            $table->string('excerpt', 1023)
                ->nullable()
                ->comment('Descripción breve del contenido');
            $table->longText('body')
                ->nullable()
                ->comment('Cuerpo del contenido');
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
        Schema::dropIfExists('contents', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['status_id']);
            $table->dropForeign(['content_type_id']);
            $table->dropForeign(['file_id']);
        });
    }
}
