<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Añade la columna `slug` a `smartplant_plants`, rellena filas previas
     * evitando colisiones e incorpora un índice único parcial sobre filas activas.
     */
    public function up(): void
    {
        Schema::table('smartplant_plants', function (Blueprint $table) {
            $table->string('slug', 160)
                ->nullable()
                ->after('name')
                ->comment('Slug único legible para URLs públicas de la planta');
        });

        $usedSlugs = [];
        $rows = DB::table('smartplant_plants')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            $base = Str::limit(Str::slug((string) $row->name), 140, '');

            if ($base === '') {
                $base = 'planta-'.$row->id;
            }

            $candidate = $base;
            $suffix = 2;

            while (in_array($candidate, $usedSlugs, true)) {
                $candidate = Str::limit($base, 140, '').'-'.$suffix;
                $suffix++;
            }

            $usedSlugs[] = $candidate;

            DB::table('smartplant_plants')
                ->where('id', $row->id)
                ->update(['slug' => $candidate]);
        }

        DB::statement('ALTER TABLE smartplant_plants ALTER COLUMN slug SET NOT NULL');
        DB::statement('CREATE UNIQUE INDEX smartplant_plants_slug_unique ON smartplant_plants (slug) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS smartplant_plants_slug_unique');

        Schema::table('smartplant_plants', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
