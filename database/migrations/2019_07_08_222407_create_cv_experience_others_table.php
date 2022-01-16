<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateCvExperienceOthersTable
 */
class CreateCvExperienceOthersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // id - translation_name_token - translation_description_token
        Schema::create('cv_experience_others', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('curriculum_id')
                ->comment('Relación con el curriculum');
            $table->foreign('curriculum_id')
                ->references('id')->on('cv')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('CASCADE');
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
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cv_experience_others', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['curriculum_id']);
        });
    }
}
