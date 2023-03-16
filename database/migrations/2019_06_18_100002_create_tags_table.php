<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTagsTable extends Migration
{
    private $tableName = 'tags';
    private $tableComment = 'tags disponibles para toda la plataforma.';

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
            $table->id();
            /*
            $table->bigInteger('file_id')
                ->nullable()
                ->comment('FK a la imagen en la tabla files');
            $table->foreign('file_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('set null');
            */
            $table->string('name', 255)
                ->index()
                ->unique()
                ->comment('Nombre de la etiqueta');
            $table->string('slug', 255)
                ->index()
                ->unique()
                ->comment('Slug para el URL');
            $table->string('description', 511)
                ->nullable()
                ->comment('Descripción acerca de lo que contendrá esta etiqueta');
            $table->string('icon', 255)->nullable()->comment('Clase css para el icono');
            $table->string('color', 255)
                ->default('#000000')
                ->comment('Código Hexadecimal del color');
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
            //$table->dropForeign(['file_id']);
        });
    }
}
