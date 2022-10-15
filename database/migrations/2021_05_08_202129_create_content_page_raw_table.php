<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentPageRawTable
 *
 * Contenido original o en otros formatos desde los que se ha compuesto el
 * html almacenado en la tabla de contenidos.
 * Por ejemplo, html, markdown, latex
 */
class CreateContentPageRawTable extends Migration
{
    private $tableName = 'content_page_raw';
    private $tableComment = 'Contenido original o en otros formatos desde los que se ha compuesto el html almacenado en la tabla de contenidos. Por ejemplo, páginas de inicio, páginas de error, etc.';

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
            $table->bigInteger('content_page_id')
                ->nullable()
                ->comment('FK a la página.');
            $table->foreign('content_page_id')
                ->references('id')->on('content_pages')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->bigInteger('available_page_raw_id')
                ->nullable()
                ->comment('FK al tipo de contenido y su formato.');
            $table->foreign('available_page_raw_id')
                ->references('id')->on('content_available_page_raw')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');

            $table->longText('content')
                ->nullable()
                ->comment('Contenido de la página en este formato.');

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
            $table->dropForeign(['available_page_raw_id']);
        });
    }
}
