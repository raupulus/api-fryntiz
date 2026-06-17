<?php

declare(strict_types=1);

namespace App\Enums;

enum NewsletterStatusEnum: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case Unsubscribed = 'unsubscribed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente de verificacion',
            self::Verified => 'Verificado',
            self::Unsubscribed => 'Dado de baja',
        };
    }
}
