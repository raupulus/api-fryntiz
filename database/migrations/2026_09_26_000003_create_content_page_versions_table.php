<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historial de versiones de las páginas (G5 de la auditoría de contenidos del
 * 2026-09-24).
 *
 * Cada vez que un guardado cambia el contenido de una página, lo que había
 * antes queda aquí. Sustituye a las copias que se guardaban como filas
 * borradas de `content_page_raw` antes de cambiar de formato. Como mucho 50
 * por página, y las de más de 30 días se borran.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_page_versions', function (Blueprint $table) {
            $table->comment('Historial de versiones de las páginas de contenido: lo que había antes de cada cambio');

            $table->bigIncrements('id')->comment('Identificador único');

            $table->unsignedBigInteger('content_page_id')->comment('Página a la que pertenece la versión');
            $table->foreign('content_page_id')->references('id')->on('content_pages')
                ->onUpdate('CASCADE')->onDelete('CASCADE');

            $table->unsignedBigInteger('user_id')->nullable()
                ->comment('Usuario que guardó el cambio que dejó esta versión en el historial; vacío si fue el sistema o si el usuario ya no existe');
            $table->foreign('user_id')->references('id')->on('users')
                ->onUpdate('CASCADE')->onDelete('SET NULL');

            $table->string('format', 16)->comment('Formato de la versión: editorjs, markdown o html');
            $table->string('title', 255)->nullable()->comment('Título de la página en esta versión');
            $table->text('content')->comment('Contenido de la página en esta versión, en su formato');
            $table->string('content_hash', 64)->comment('SHA-256 del formato y el contenido');
            $table->string('reason', 20)
                ->comment('Por qué se guardó: save (guardado), format_change (cambio de formato), restore (recuperar una versión), draft_restore (recuperar un borrador) o emptied (la página se vació)');

            $table->timestamp('created_at')->nullable()->comment('Fecha en la que se guardó la versión (UTC)');

            $table->index(['content_page_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_page_versions');
    }
};
