<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lo que sólo comprobaba el código de las páginas pasa también a la base de
 * datos, y los índices que faltaban para leerlas (auditorías externas del
 * 2026-09-29).
 *
 * - El slug de una página es único dentro de su contenido, contando la
 *   papelera: lo mismo que pide el formulario. Sin él, dos guardados a la vez
 *   podían repetirlo y la API serviría una cualquiera de las dos.
 * - Todas las consultas de páginas van por contenido y ordenan por `order`.
 * - `content_page_raw` no tenía ningún índice: cada página se leía recorriendo
 *   la tabla entera. Y un formato por página (sin contar los borrados), que es
 *   lo que da por hecho `ContentPageFormatService::putRaw()`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_pages', function (Blueprint $table) {
            $table->unique(['content_id', 'slug']);
            $table->index(['content_id', 'order']);
        });

        Schema::table('content_page_raw', function (Blueprint $table) {
            $table->index('content_page_id');
        });

        DB::statement('CREATE UNIQUE INDEX content_page_raw_page_format_unique ON content_page_raw (content_page_id, available_page_raw_id) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS content_page_raw_page_format_unique');

        Schema::table('content_page_raw', function (Blueprint $table) {
            $table->dropIndex(['content_page_id']);
        });

        Schema::table('content_pages', function (Blueprint $table) {
            $table->dropIndex(['content_id', 'order']);
            $table->dropUnique(['content_id', 'slug']);
        });
    }
};
