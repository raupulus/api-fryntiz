<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateContentAvailablePageRawTable
 */
class CreateContentAvailablePageRawTable extends Migration
{
    private $tableName = 'content_available_page_raw';
    private $tableComment = 'Formatos de edición para el contenido sin procesar (con el que se generará el html de cada página para el contenido.';

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

            $table->string('name', 255)
                ->comment('Nombre del formato de edición');
            $table->string('description', 255)
                ->nullable()
                ->comment('Descripción del formato de edición');
            $table->string('type', 255)
                ->comment('Tipo de formato de edición. Ej: texto plano, markdown, html, latex, json, hoja de cálculo...');
            $table->string('extension', 255)
                ->comment('Extensión del formato de edición. Ej md, html, txt, doc, docx, xls, xlsx, json...');

            $table->timestamps();
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
        Schema::dropIfExists($this->tableName);
    }
}
