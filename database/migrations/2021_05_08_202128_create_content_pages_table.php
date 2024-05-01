<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentPagesTable
 *
 * Cada una de las páginas que componen el contenido
 */
class CreateContentPagesTable extends Migration
{
    private $tableName = 'content_pages';
    private $tableComment = 'Cada una de las páginas que componen el contenido';

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
            $table->bigInteger('content_id')
                ->index()
                ->nullable()
                ->comment('FK al usuario propietario del post');
            $table->foreign('content_id')
                ->references('id')->on('contents')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->bigInteger('image_id')
                ->nullable()
                ->comment('FK a la imagen en la tabla files');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('SET NULL');
            $table->bigInteger('current_page_raw_id')
                ->nullable()
                ->comment('FK al tipo de contenido desde el que se ha procesado el html actual para el campo "content". En caso de ser NULL, se está utilizando directamente el campo "content"');
            $table->foreign('current_page_raw_id')
                ->references('id')->on('content_available_page_raw')
                ->onUpdate('cascade')
                ->onDelete('SET NULL');

            $table->string('title', 255)
                ->nullable()
                ->comment('Título de la página');
            $table->string('slug', 255)
                ->nullable()
                ->comment('Slug de la página');
            $table->text('content')
                ->nullable()
                ->comment('Contenido de la página en html procesado');
            $table->integer('order')
                ->nullable()
                ->comment('Orden de la página al mostrarse');

            $table->timestamps();
            $table->softDeletes();
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
            $table->dropForeign(['content_id']);
            $table->dropForeign(['image_id']);
            $table->dropForeign(['current_page_raw_id']);
        });
    }
}
