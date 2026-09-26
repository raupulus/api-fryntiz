<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Estados de un contenido, con el id que tienen en `content_available_status`.
 *
 * El orden es el de la base real, que viene de la v1: 2 es «programado» y 3
 * «publicado». En mayo de 2026 la v2 los cambió en el seeder (2 = publicado) y
 * todo el código se escribió sobre ese orden inventado: la API buscaba los
 * contenidos publicados con el id de «programado». Los ids no se tocan; si
 * alguna vez no casan con la base, `project:check-config` lo avisa.
 *
 * Qué pasa al entrar en cada estado lo decide `Content` al guardar (ver
 * `Content::applyPublicationRules()`).
 */
enum ContentStatusEnum: int
{
    case Draft = 1;
    case Scheduled = 2;
    case Published = 3;
    case NotPublished = 4;
    case CopyrightProtected = 5;
    case ToRemove = 6;

    /**
     * Slug de la fila en `content_available_status`.
     */
    public function slug(): string
    {
        return match ($this) {
            self::Draft => 'draft',
            self::Scheduled => 'programmed',
            self::Published => 'published',
            self::NotPublished => 'not-published',
            self::CopyrightProtected => 'copyright-protected',
            self::ToRemove => 'to-remove',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Scheduled => 'Programado',
            self::Published => 'Publicado',
            self::NotPublished => 'No publicado',
            self::CopyrightProtected => 'Protegido por copyright',
            self::ToRemove => 'Para eliminar',
        };
    }
}
