<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\Models\Content\Content;
use App\Models\Content\ContentSeo;

/**
 * Servicio encargado de la gestión y actualización de metadatos SEO para los contenidos.
 */
class ContentSeoService
{
    /**
     * Inserta o actualiza los datos SEO asociados a un contenido específico.
     *
     * @param \App\Models\Content\Content $content Instancia del contenido asociado.
     * @param array $data Datos SEO a actualizar (títulos, descripciones, palabras clave).
     * @return \App\Models\Content\ContentSeo El modelo SEO actualizado o creado.
     */
    public function upsert(Content $content, array $data): ContentSeo
    {
        return ContentSeo::updateOrCreate(
            ['content_id' => $content->id],
            $data
        );
    }
}
