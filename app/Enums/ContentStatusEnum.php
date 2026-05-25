<?php
namespace App\Enums;
enum ContentStatusEnum: int
{
    case Draft = 1;
    case Published = 2;
    case Scheduled = 3;
    case Archived = 4;
    case Deleted = 5;
    public function label(): string
    {
        return match($this) {
            self::Draft => 'Borrador',
            self::Published => 'Publicado',
            self::Scheduled => 'Programado',
            self::Archived => 'Archivado',
            self::Deleted => 'Eliminado',
        };
    }
}
