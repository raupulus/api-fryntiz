<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTechnologiesTable extends Migration
{
    private $tableName = 'technologies';
    private $tableComment = 'Tecnologías disponibles para toda la plataforma.';

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
            $table->bigInteger('image_id')
                ->nullable()
                ->comment('FK a la imagen en la tabla files');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->string('name', 255)
                ->index()
                ->unique()
                ->comment('Nombre de la tecnología');
            $table->string('slug', 255)
                ->index()
                ->unique()
                ->comment('Slug para el URL');
            $table->string('description', 255)
                ->nullable()
                ->comment('Descripción breve de esta tecnología');
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
            $table->dropForeign(['image_id']);
        });
    }
}
