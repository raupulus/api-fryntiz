<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateCvAcademicTrainingTable
 */
class CreateCvAcademicTrainingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv_academic_training', function (Blueprint $table) {
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
                ->comment('Título obtenido');
            $table->string('entity', 511)
                ->nullable()
                ->comment('Entidad o empresa emisora');
            $table->string('credential_id', 511)
                ->nullable()
                ->comment('Identificador de la Credencial obtenida');
            $table->string('credential_url', 511)
                ->nullable()
                ->comment('Url hacia la Credencial obtenida');
            $table->string('learned', 511)
                ->nullable()
                ->comment('Conocimientos adquiridos');
            $table->text('description')
                ->nullable()
                ->comment('Descripción');
            $table->text('note')
                ->nullable()
                ->comment('Notas');
            $table->integer('hours')
                ->nullable()
                ->comment('Horas de formación');
            $table->string('instructor', 511)
                ->nullable()
                ->comment('Instructor de la formación');
            $table->boolean('expires')
                ->default(false)
                ->comment('¿Expira la validez?');
            $table->dateTime('expires_at')
                ->nullable()
                ->comment('Fecha de expiración');
            $table->dateTime('expedition_at')
                ->nullable()
                ->comment('Fecha de expedición');
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
        Schema::dropIfExists('cv_academic_training', function (Blueprint $table) {
            $table->dropForeign(['image_id']);
            $table->dropForeign(['curriculum_id']);
        });
    }
}
