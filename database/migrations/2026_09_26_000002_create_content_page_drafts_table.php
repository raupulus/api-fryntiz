<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Borradores de las páginas en el servidor (D1 y P4 de la auditoría de
 * contenidos del 2026-09-24).
 *
 * Mientras se escribe, el panel guarda aquí lo que hay en pantalla cada 30 s.
 * Uno por usuario y página: cada borrador es sólo de quien lo escribió. Si se
 * cuelga el navegador, se recarga o caduca la sesión, lo escrito sigue aquí.
 * Se borra al guardar la página y caduca a los 30 días.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_page_drafts', function (Blueprint $table) {
            $table->comment('Borradores de las páginas de contenido: lo que cada usuario tiene escrito sin guardar');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedBigInteger('user_id')->comment('Usuario que escribe el borrador: sólo él lo ve y lo recupera');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->unsignedBigInteger('content_id')->comment('Contenido al que pertenece la página');
            $table->foreign('content_id')->references('id')->on('contents')
                ->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->unsignedBigInteger('content_page_id')->nullable()
                ->comment('Página del borrador; vacío si es una página nueva que aún no se ha guardado');
            $table->foreign('content_page_id')->references('id')->on('content_pages')
                ->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->string('format', 16)->comment('Formato en el que se escribe: editorjs, markdown o html');
            $table->string('title', 255)->nullable()->comment('Título de la página en el borrador');
            $table->string('slug', 255)->nullable()->comment('Slug de la página en el borrador');

            $table->unsignedBigInteger('image_id')->nullable()->comment('Imagen de la página en el borrador');
            $table->foreign('image_id')->references('id')->on('files')
                ->onUpdate('CASCADE')->onDelete('SET NULL');

            $table->text('content')->comment('Contenido escrito, en su formato');
            $table->string('content_hash', 64)
                ->comment('SHA-256 del formato, título, slug, imagen y contenido: si no cambia, no se vuelve a escribir');
            $table->timestamp('base_page_updated_at')->nullable()
                ->comment('Fecha de la última modificación de la página cuando se empezó a escribir: si la página se guarda después, el borrador lo avisa');

            $table->timestamp('created_at')->nullable()->comment('Fecha de creación del borrador (UTC)');
            $table->timestamp('updated_at')->nullable()->comment('Fecha del último cambio del borrador (UTC): caduca a los 30 días');

            $table->unique(['user_id', 'content_page_id']);
            $table->index('content_id');
            $table->index('updated_at');
        });

        // Una página nueva todavía no tiene id: un borrador por usuario y
        // contenido. (El único de arriba no lo cubre: dos NULL no chocan.)
        DB::statement('CREATE UNIQUE INDEX content_page_drafts_new_page_unique ON content_page_drafts (user_id, content_id) WHERE content_page_id IS NULL');
    }

    public function down(): void
    {
        Schema::dropIfExists('content_page_drafts');
    }
};
