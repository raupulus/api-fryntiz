<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateContentCategoriesAvailabeTable
 */
class CreateContentAvailableCategoriesTable extends Migration
{
    private $tableName = 'content_available_categories';
    private $tableComment = 'Categorías disponibles para el contenido.';

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create($this->tableName, function (Blueprint
                                                              $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->bigInteger('user_id')
                ->nullable()
                ->comment('FK a la tabla users');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->bigInteger('platform_id')
                ->index()
                ->nullable()
                ->comment('FK a la sección que se asocia la categoría con el tipo de plataforma');
            $table->foreign('platform_id')
                ->references('id')->on('platforms')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
            $table->bigInteger('file_id')
                ->nullable()
                ->comment('FK a la imagen en la tabla files');
            $table->foreign('file_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->string('name', 511)
                ->index()
                ->unique()
                ->comment('Nombre de la categoría');
            $table->string('slug', 255)
                ->index()
                ->unique()
                ->comment('Slug para el URL');
            $table->string('description', 1023)
                ->nullable()
                ->comment('Descripción acerca de lo que contendrá esta categoría');
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
        Schema::dropIfExists($this->tableName);
    }
}
