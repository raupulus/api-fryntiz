<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Class CreateCvTable
 */
class CreateCvTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cv', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')
                ->comment('Relación con el usuario');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade');
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL');
            $table->string('title', 511)
                ->comment('Título para el curriculum');
            $table->text('presentation')
                ->nullable()
                ->comment('Contenido para la presentación del curriculum');
            $table->boolean('is_active')
                ->nullable()
                ->default(0)
                ->comment('Indica si está activo');
            $table->boolean('is_downloadable')
                ->nullable()
                ->default(0)
                ->comment('Indica si permite descargar el curriculum');
            $table->boolean('is_default')
                ->nullable()
                ->default(0)
                ->comment('Indica si es el curriculum por defecto');
            $table->boolean('is_public')
                ->nullable()
                ->default(0)
                ->comment('Indica si su visibilidad es pública');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cv_repositories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['image_id']);
        });
    }
}
