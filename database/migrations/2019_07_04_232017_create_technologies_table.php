<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateTechnologiesTable
 *
 * Tabla para almacenar Tecnologías disponibles para toda la plataforma.
 */
class CreateTechnologiesTable extends Migration
{
    private $tableName = 'technologies';

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
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
            $table->string('name', '255')
                ->unique()
                ->comment('Nombre de la tecnología.');
            $table->string('slug', '255')
                ->unique()
                ->comment('Slug para el URL.');
            $table->text('description')
                ->nullable()
                ->comment('Descripción breve de esta tecnología.');
            $table->string('color', '255')
                ->default('#000000')
                ->comment('Código Hexadecimal del color.');

            $table->timestamps();
            $table->softDeletes();
        });

        $comment = 'Tecnologías disponibles para toda la plataforma.';

        DB::statement("COMMENT ON TABLE {$this->tableName} IS '{$comment}'");
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
