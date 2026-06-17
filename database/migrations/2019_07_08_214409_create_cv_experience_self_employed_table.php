<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class CreateCvExperienceSelfEmployedTable
 */
class CreateCvExperienceSelfEmployedTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv_experience_self_employed', function (Blueprint $table) {
            $table->comment('Almacena los datos del apartado de experience self employed para la generación del currículum vitae de los usuarios.');
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
                ->comment('Título de la experiencia');
            $table->string('position', 255)
                ->nullable()
                ->comment('Puesto ocupado en la experiencia');
            $table->string('company', 511)
                ->nullable()
                ->comment('Empresa donde trabajó');
            $table->text('description')
                ->nullable()
                ->comment('Descripción');
            $table->text('note')
                ->nullable()
                ->comment('Notas');
            $table->dateTime('start_at')
                ->nullable()
                ->comment('Fecha de inicio');
            $table->dateTime('end_at')
                ->nullable()
                ->comment('Fecha de fin');
            $table->timestamps()->comment('Marcas de tiempo utilizadas por Eloquent para llevar el registro de creación y última actualización.');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cv_experience_self_employed', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['curriculum_id']);
        });
    }
}
