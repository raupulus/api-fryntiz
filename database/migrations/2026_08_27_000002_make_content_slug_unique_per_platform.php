<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * N206 — el slug de un contenido es único **dentro de su plataforma**, no en
 * todo el sistema.
 *
 * `contents.slug` tenía un índice único global, así que dos webs distintas no
 * podían tener las dos un artículo llamado «instalar-docker». Con la URL
 * anidada (`/platforms/{p}/contents/{slug}`) lo que desambigua es el par, y el
 * índice pasa a reflejarlo.
 *
 * Aborta si hay filas que ya incumplirían la nueva regla, en vez de fallar a
 * medias con un error de índice ilegible.
 */
return new class extends Migration
{
    public function up(): void
    {
        $duplicados = DB::table('contents')
            ->select('platform_id', 'slug', DB::raw('count(*) as total'))
            ->groupBy('platform_id', 'slug')
            ->havingRaw('count(*) > 1')
            ->get();

        if ($duplicados->isNotEmpty()) {
            $detalle = $duplicados
                ->map(fn ($row) => "platform_id={$row->platform_id} slug={$row->slug} ({$row->total})")
                ->implode('; ');

            throw new RuntimeException(
                'Hay contenidos con el mismo slug dentro de la misma plataforma. '.
                'Renómbralos antes de migrar: '.$detalle
            );
        }

        Schema::table('contents', function (Blueprint $table) {
            // El nombre del índice único que crea Laravel para ->unique() sobre
            // una columna es "{tabla}_{columna}_unique".
            $table->dropUnique('contents_slug_unique');
            $table->unique(['platform_id', 'slug'], 'contents_platform_id_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('contents', function (Blueprint $table) {
            $table->dropUnique('contents_platform_id_slug_unique');
            $table->unique('slug', 'contents_slug_unique');
        });
    }
};
