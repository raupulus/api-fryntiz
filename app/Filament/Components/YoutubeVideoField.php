<?php

declare(strict_types=1);

namespace App\Filament\Components;

use Closure;
use Filament\Forms\Components\Field;

/**
 * Campo Filament que integra el buscador de vídeos de YouTube
 * (plugin JS propio `public/js/youtube_video_search.js`).
 *
 * El estado del campo es el ID del vídeo de YouTube (`youtube_video_id`).
 * Al seleccionar un vídeo en el modal de búsqueda, se rellena el estado y se
 * muestra una vista previa embebida.
 *
 * El canal sobre el que buscar se resuelve dinámicamente según la plataforma
 * seleccionada en el formulario (mapa id_plataforma => youtube_channel_id).
 */
class YoutubeVideoField extends Field
{
    protected string $view = 'filament.components.youtube-video-field';

    protected string|Closure|null $apiKey = null;

    protected array|Closure $channels = [];

    protected string|Closure $platformStatePath = 'data.platform_id';

    /**
     * Clave de API de YouTube (Google).
     */
    public function apiKey(string|Closure|null $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Mapa [platform_id => youtube_channel_id] para resolver el canal según
     * la plataforma seleccionada.
     */
    public function channels(array|Closure $channels): static
    {
        $this->channels = $channels;

        return $this;
    }

    /**
     * Ruta de estado Livewire del campo de plataforma del formulario.
     */
    public function platformStatePath(string|Closure $path): static
    {
        $this->platformStatePath = $path;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->evaluate($this->apiKey);
    }

    public function getChannels(): array
    {
        return $this->evaluate($this->channels);
    }

    public function getPlatformStatePath(): string
    {
        return $this->evaluate($this->platformStatePath);
    }
}
