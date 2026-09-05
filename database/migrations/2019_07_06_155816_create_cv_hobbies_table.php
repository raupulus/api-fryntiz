<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateCvHobbiesTable
 */
class CreateCvHobbiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv_hobbies', function (Blueprint $table) {
            $table->comment('Almacena los datos del apartado de hobbies para la generación del currículum vitae de los usuarios.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('curriculum_id')
                ->comment('Relación con el curriculum');
            $table->foreign('curriculum_id')
                ->references('id')->on('cv')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE')->comment('Clave foránea que relaciona este registro con el curriculum al que pertenece.');
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE')->comment('Clave foránea que relaciona este registro con el image al que pertenece.');
            $table->string('title', 511)
                ->nullable()
                ->comment('Título del hobby');
            $table->text('description')
                ->nullable()
                ->comment('Descripción del hobby');
            $table->string('url', 511)
                ->nullable()
                ->comment('URL del hobby');
            $table->timestamps()->comment('Marcas de tiempo utilizadas por Eloquent para llevar el registro de creación y última actualización.');

            $table->unsignedInteger('position')
                ->default(0)
                ->comment('Orden manual dentro de su sección del currículum.');
            $table->index(['curriculum_id', 'position'], 'cv_hobbies_curriculum_position_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cv_hobbies', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['curriculum_id']);
        });
    }
}
