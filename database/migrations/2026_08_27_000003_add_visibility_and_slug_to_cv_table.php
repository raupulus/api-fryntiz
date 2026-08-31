<?php

declare(strict_types=1);

use App\Enums\CurriculumVisibilityEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Multi-currículum: cada CV con su URL, su visibilidad y su PDF (B1, B2, B5).
 *
 * El módulo ya tenía dieciocho tablas para que pudiera haber varios CV, y sin
 * embargo no había forma de dirigirse a uno concreto: la API devolvía siempre
 * «el del superadmin». Faltaban tres cosas:
 *
 *  - `slug`: la URL de cada CV.
 *  - `visibility` + `share_token`: privado / compartido por enlace / público.
 *    Un booleano `is_public` no da el estado intermedio, que es justo el que
 *    hace falta para mandarle a alguien un CV hecho a medida sin publicarlo.
 *  - `pdf_path` + `pdf_needs_regeneration`: el PDF se genera y se guarda; al
 *    tocar cualquier tabla del CV se marca para regenerar.
 *
 * `is_public` se migra a `visibility` y se queda: lo usan formularios antiguos
 * y borrarlo es tarea de la fase de limpieza.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cv', function (Blueprint $table) {
            $table->string('slug', 255)
                ->nullable()
                ->after('title')
                ->comment('Slug para la URL pública del currículum.');

            $table->string('visibility', 16)
                ->default(CurriculumVisibilityEnum::Private->value)
                ->after('slug')
                ->comment('private | shared | public. Shared se sirve con noindex.');

            $table->string('share_token', 64)
                ->nullable()
                ->after('visibility')
                ->comment('Token del enlace privado para compartir el CV.');

            $table->string('pdf_path', 512)
                ->nullable()
                ->after('share_token')
                ->comment('Ruta del PDF generado.');

            $table->boolean('pdf_needs_regeneration')
                ->default(true)
                ->after('pdf_path')
                ->comment('Se marca al editar cualquier tabla del CV.');

            $table->timestamp('pdf_generated_at')
                ->nullable()
                ->after('pdf_needs_regeneration')
                ->comment('Cuándo se generó el PDF actual.');
        });

        // Rellena slug y visibilidad de lo que ya hubiera.
        foreach (DB::table('cv')->get(['id', 'title', 'is_public']) as $cv) {
            DB::table('cv')->where('id', $cv->id)->update([
                'slug' => $this->uniqueSlug($cv->title, $cv->id),
                'visibility' => $cv->is_public
                    ? CurriculumVisibilityEnum::Public->value
                    : CurriculumVisibilityEnum::Private->value,
            ]);
        }

        Schema::table('cv', function (Blueprint $table) {
            $table->string('slug', 255)->nullable(false)->change();
            $table->unique('slug', 'cv_slug_unique');
            $table->unique('share_token', 'cv_share_token_unique');
            $table->index('visibility', 'cv_visibility_index');
            $table->index('pdf_needs_regeneration', 'cv_pdf_needs_regeneration_index');
        });
    }

    public function down(): void
    {
        Schema::table('cv', function (Blueprint $table) {
            $table->dropUnique('cv_slug_unique');
            $table->dropUnique('cv_share_token_unique');
            $table->dropIndex('cv_visibility_index');
            $table->dropIndex('cv_pdf_needs_regeneration_index');
            $table->dropColumn([
                'slug', 'visibility', 'share_token',
                'pdf_path', 'pdf_needs_regeneration', 'pdf_generated_at',
            ]);
        });
    }

    /**
     * Slug a partir del título, con el id detrás si ya está cogido.
     */
    private function uniqueSlug(?string $title, int $id): string
    {
        $base = Str::slug((string) $title) ?: 'curriculum';

        $existe = DB::table('cv')
            ->where('slug', $base)
            ->where('id', '!=', $id)
            ->exists();

        return $existe ? $base.'-'.$id : $base;
    }
};
