<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
            $table->comment('Almacena los registros correspondientes a cv para su integración y uso general en el sistema.');
            $table->engine = 'InnoDB';
            $table->charset = 'utf8';
            $table->collation = 'utf8_unicode_ci';
            $table->bigIncrements('id')->comment('Identificador único autoincremental de este registro en la base de datos.');
            $table->unsignedBigInteger('user_id')
                ->comment('Relación con el usuario');
            $table->foreign('user_id')
                ->references('id')->on('users')
                ->onUpdate('cascade')
                ->onDelete('cascade')->comment('Clave foránea que relaciona este registro con el user al que pertenece.');
            $table->unsignedBigInteger('image_id')
                ->nullable()
                ->comment('Relación con la imagen asociada');
            $table->foreign('image_id')
                ->references('id')->on('files')
                ->onUpdate('CASCADE')
                ->onDelete('SET NULL')->comment('Clave foránea que relaciona este registro con el image al que pertenece.');
            $table->string('title', 511)
                ->comment('Título para el curriculum');
            $table->string('slug', 255)
                ->unique('cv_slug_unique')
                ->comment('Slug para la URL pública del currículum.');
            $table->string('visibility', 16)
                ->default('private')
                ->comment('private | shared | public. Shared se sirve con noindex.');
            $table->string('share_token', 64)
                ->nullable()
                ->unique('cv_share_token_unique')
                ->comment('Token del enlace privado para compartir el CV.');
            $table->string('pdf_path', 512)
                ->nullable()
                ->comment('Ruta del PDF generado.');
            $table->boolean('pdf_needs_regeneration')
                ->default(true)
                ->comment('Se marca al editar cualquier tabla del CV.');
            $table->timestamp('pdf_generated_at')
                ->nullable()
                ->comment('Cuándo se generó el PDF actual.');
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
            $table->timestamps()->comment('Marcas de tiempo utilizadas por Eloquent para llevar el registro de creación y última actualización.');
            $table->softDeletes()->comment('Marca de tiempo empleada por Eloquent para habilitar el borrado lógico (soft deletes).');

            $table->index('visibility', 'cv_visibility_index');
            $table->index('pdf_needs_regeneration', 'cv_pdf_needs_regeneration_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cv', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['image_id']);
        });
    }
}
