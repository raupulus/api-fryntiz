<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\Content;
use App\Models\Platform;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

/**
 * Servicio principal del gestor de contenidos (CMS).
 * Maneja la lógica de negocio para obtener contenidos, destacados, y publicaciones programadas.
 */
class ContentService
{
    /**
     * Obtiene un contenido publicado específico en base a su slug y al slug de la plataforma a la que pertenece.
     * Carga de manera ansiosa relaciones clave como páginas, SEO, metadatos y tecnologías.
     *
     * @param  string  $platformSlug  Slug de la plataforma origen.
     * @param  string  $contentSlug  Slug único del contenido a buscar.
     * @return Content|null Modelo de contenido o null si no se encuentra/no está publicado.
     */
    public function getBySlug(string $platformSlug, string $contentSlug): ?Content
    {
        return Content::with(['type', 'status', 'pages', 'seo', 'metadata', 'technologies', 'image.fileType'])
            ->withCount('pages')
            ->withSum('dailyViews as views_count', 'views')
            ->whereHas('platform', fn ($q) => $q->where('slug', $platformSlug))
            ->where('slug', $contentSlug)
            ->published()
            ->first();
    }

    /**
     * Obtiene los contenidos destacados para una plataforma determinada.
     *
     * @param  Platform  $platform  Plataforma de la cual buscar destacados.
     * @param  int  $limit  Cantidad máxima de registros a devolver (por defecto 10).
     * @return Collection Colección de contenidos destacados.
     */
    public function getFeaturedForPlatform(Platform $platform, int $limit = 10): Collection
    {
        return Content::with(['seo', 'metadata', 'image.fileType'])
            ->forPlatform($platform->id)
            ->published()
            ->featured()
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtiene de forma paginada los contenidos publicados de una plataforma, filtrados por un tipo específico.
     *
     * @param  Platform  $platform  Plataforma origen de los contenidos.
     * @param  int  $typeId  Identificador del tipo de contenido (ej: artículo, proyecto, tutorial).
     * @param  int  $perPage  Cantidad de resultados por página (por defecto 15).
     * @return LengthAwarePaginator Paginador con los resultados.
     */
    public function getByTypeForPlatform(Platform $platform, int $typeId, int $perPage = 15): LengthAwarePaginator
    {
        return Content::with(['seo', 'image.fileType'])
            ->forPlatform($platform->id)
            ->ofType($typeId)
            ->published()
            ->orderByDesc('published_at')
            ->paginate($perPage);
    }

    /**
     * Obtiene contenidos relacionados (de la misma plataforma y tipo) excluyendo el contenido actual.
     *
     * @param  Content  $content  Contenido base para buscar relacionados.
     * @param  int  $limit  Límite de contenidos sugeridos (por defecto 5).
     * @return Collection Colección de contenidos relacionados.
     */
    public function getRelated(Content $content, int $limit = 5): Collection
    {
        return Content::with(['type', 'seo', 'image.fileType'])
            ->where('id', '!=', $content->id)
            ->forPlatform($content->platform_id)
            ->ofType($content->type_id)
            ->published()
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Rutina que transiciona el estado de los contenidos programados a 'publicado'
     * si su fecha de publicación es menor o igual a la actual.
     *
     * @return int Número de registros actualizados.
     */
    public function publishScheduled(): int
    {
        return Content::scheduled()
            ->where('published_at', '<=', now())
            ->update(['status_id' => 2]);
    }
}
