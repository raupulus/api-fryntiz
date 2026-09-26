<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ficheros de contenido que ya no usa nada (C2 de la auditoría de contenidos
 * del 2026-09-24).
 *
 * Al guardar una página se marcan los ficheros del contenido que no aparecen
 * en ninguna página, versión, borrador ni portada, y se desmarcan si vuelven a
 * aparecer. Una tarea diaria borra los que llevan 30 días marcados.
 *
 * La clave del contenido pasa de CASCADE a SET NULL: al eliminar un contenido
 * definitivamente, sus filas se quedan (marcadas y sin contenido) para que la
 * tarea borre después los ficheros. Con CASCADE desaparecían con él y los
 * ficheros se quedaban en el disco sin que nada apuntase a ellos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_files', function (Blueprint $table) {
            $table->timestamp('unused_since')->nullable()
                ->comment('Desde cuándo (UTC) no lo usa ninguna página, versión, borrador ni portada; vacío si se usa. A los 30 días se borra el fichero');
            $table->index('unused_since');

            $table->dropForeign(['content_id']);
            $table->foreign('content_id')->references('id')->on('contents')
                ->onUpdate('CASCADE')->onDelete('SET NULL');
        });
    }

    public function down(): void
    {
        Schema::table('content_files', function (Blueprint $table) {
            $table->dropIndex(['unused_since']);
            $table->dropColumn('unused_since');

            $table->dropForeign(['content_id']);
            $table->foreign('content_id')->references('id')->on('contents')
                ->onUpdate('CASCADE')->onDelete('CASCADE');
        });
    }
};
