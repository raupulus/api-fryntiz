<?php

declare(strict_types=1);

namespace App\Services\Cv;

use App\Models\CV\Curriculum;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Currículums.
 *
 * Antes este servicio sólo sabía hacer una cosa: «dame el CV de este usuario»,
 * con `->first()`. Con dieciocho tablas preparadas para tener varios CV, eso
 * significaba que sólo se podía llegar a uno, y por azar (el primero que
 * devolviera la base de datos).
 */
class CurriculumService
{
    /**
     * Todas las relaciones de secciones, para cargarlas de una vez.
     *
     * @var array<int, string>
     */
    public const SECTIONS = [
        'repositories', 'services', 'collaborations', 'hobbies',
        'jobs', 'projects', 'academicTraining',
        'academicComplementary', 'academicComplementaryOnline',
        'experienceAccredited', 'experienceNoAccredited',
        'experienceSelfEmployed', 'experienceAdditional',
        'experienceOther', 'skills',
    ];

    /**
     * Listado público paginado (B3): sólo los marcados como públicos.
     *
     * @return LengthAwarePaginator<int, Curriculum>
     */
    public function publicOnly(int $porPagina = 25): LengthAwarePaginator
    {
        return Curriculum::query()
            ->publicOnly()
            ->orderByDesc('is_default')
            ->orderBy('title')
            ->paginate($porPagina);
    }

    /**
     * Un CV por su slug, con todas sus secciones.
     */
    public function bySlug(string $slug): ?Curriculum
    {
        return Curriculum::query()
            ->with(self::SECTIONS)
            ->where('slug', $slug)
            ->first();
    }

    /**
     * Un CV por su token de compartición.
     *
     * Se busca por token y luego se comprueba la visibilidad, para que un token
     * de un CV que ha pasado a privado deje de valer.
     */
    public function byShareToken(string $token): ?Curriculum
    {
        $cv = Curriculum::query()
            ->with(self::SECTIONS)
            ->where('share_token', $token)
            ->first();

        return $cv?->isVisibleTo($token) ? $cv : null;
    }

    /**
     * El CV predeterminado de la plataforma: el marcado como `is_default`
     * entre los públicos.
     */
    public function defaultCurriculum(): ?Curriculum
    {
        return Curriculum::query()
            ->with(self::SECTIONS)
            ->publicOnly()
            ->orderByDesc('is_default')
            ->first();
    }
}
