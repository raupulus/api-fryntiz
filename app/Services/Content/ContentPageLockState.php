<?php

declare(strict_types=1);

namespace App\Services\Content;

use Carbon\CarbonInterface;

/**
 * Cómo está el bloqueo de una página para quien pregunta
 * (`ContentPageLockService`).
 */
final readonly class ContentPageLockState
{
    public const FREE = 'free';

    public const MINE = 'mine';

    public const OTHER_TAB = 'other_tab';

    public const OTHER_USER = 'other_user';

    private function __construct(
        public string $status,
        public ?int $holderId = null,
        public ?string $holderName = null,
        public ?CarbonInterface $since = null,
    ) {}

    public static function free(): self
    {
        return new self(self::FREE);
    }

    public static function mine(?CarbonInterface $since): self
    {
        return new self(self::MINE, since: $since);
    }

    public static function otherTab(int $holderId, ?CarbonInterface $since): self
    {
        return new self(self::OTHER_TAB, $holderId, since: $since);
    }

    public static function otherUser(int $holderId, string $holderName, ?CarbonInterface $since): self
    {
        return new self(self::OTHER_USER, $holderId, $holderName, $since);
    }

    /**
     * Si quien pregunta puede editar: la tiene él en esta pestaña o no la
     * tiene nadie.
     */
    public function canEdit(): bool
    {
        return $this->status === self::FREE || $this->status === self::MINE;
    }

    /**
     * El aviso para quien la ve en lectura.
     */
    public function message(): ?string
    {
        return match ($this->status) {
            self::OTHER_TAB => 'Ya la tienes abierta en otra pestaña: aquí se ve en lectura.',
            self::OTHER_USER => sprintf(
                '%s la está editando%s: se ve en lectura hasta que la deje.',
                $this->holderName,
                $this->since !== null ? ' desde '.$this->since->locale('es')->diffForHumans() : '',
            ),
            default => null,
        };
    }
}
