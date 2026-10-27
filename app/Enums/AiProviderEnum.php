<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\Ai\AiImageProvider;
use App\Services\Ai\AiTextProvider;
use App\Services\Ai\Providers\GeminiImageProvider;
use App\Services\Ai\Providers\GeminiTextProvider;
use App\Services\Ai\Providers\MiniMaxImageProvider;
use App\Services\Ai\Providers\MiniMaxTextProvider;
use App\Services\Ai\Providers\MistralImageProvider;
use App\Services\Ai\Providers\MistralTextProvider;
use App\Services\Ai\Providers\OpenAiImageProvider;
use App\Services\Ai\Providers\ResponsesTextProvider;
use App\Services\Ai\Providers\XaiImageProvider;

/**
 * Los proveedores de IA que puede usar el asistente del editor de páginas.
 *
 * El modelo y la clave de cada uno se ponen en el panel (Configuración →
 * Proveedores de IA, sólo superadmin), no aquí: ver `App\Models\AiProvider`.
 *
 * OpenAI y xAI hablan la misma API («Responses»): xAI da Chat Completions por
 * obsoleta y OpenAI recomienda Responses. Mistral sigue con su Chat
 * Completions, y Gemini con la suya.
 */
enum AiProviderEnum: string
{
    case Gemini = 'gemini';
    case Mistral = 'mistral';
    case Xai = 'xai';
    case OpenAi = 'openai';
    case MiniMax = 'minimax';

    /**
     * El nombre corto, el que sale en los mensajes («Gemini no tiene saldo»).
     */
    public function label(): string
    {
        return match ($this) {
            self::Gemini => 'Gemini',
            self::Mistral => 'Mistral',
            self::Xai => 'Grok',
            self::OpenAi => 'OpenAI',
            self::MiniMax => 'MiniMax',
        };
    }

    /**
     * El nombre con su empresa, para elegirlo en el panel.
     */
    public function fullLabel(): string
    {
        return match ($this) {
            self::Gemini => 'Gemini (Google)',
            self::Mistral => 'Mistral',
            self::Xai => 'Grok (xAI)',
            self::OpenAi => 'OpenAI',
            self::MiniMax => 'MiniMax',
        };
    }

    /**
     * @return class-string<AiTextProvider>
     */
    public function connector(): string
    {
        return match ($this) {
            self::Gemini => GeminiTextProvider::class,
            self::Mistral => MistralTextProvider::class,
            self::Xai, self::OpenAi => ResponsesTextProvider::class,
            self::MiniMax => MiniMaxTextProvider::class,
        };
    }

    public function baseUrl(): string
    {
        return match ($this) {
            self::Gemini => 'https://generativelanguage.googleapis.com/v1beta',
            self::Mistral => 'https://api.mistral.ai/v1',
            self::Xai => 'https://api.x.ai/v1',
            self::OpenAi => 'https://api.openai.com/v1',
            self::MiniMax => 'https://api.minimax.io/v1',
        };
    }

    /**
     * Dónde se ven los identificadores de los modelos de la API.
     */
    public function modelsUrl(): string
    {
        return match ($this) {
            self::Gemini => 'https://ai.google.dev/gemini-api/docs/models',
            self::Mistral => 'https://docs.mistral.ai/getting-started/models',
            self::Xai => 'https://docs.x.ai/docs/models',
            self::OpenAi => 'https://platform.openai.com/docs/models',
            self::MiniMax => 'https://platform.minimaxi.com/document/ChatCompletion%20v2',
        };
    }

    /**
     * Un modelo de texto que existía al escribir esto (comprobado en la
     * página de modelos de cada uno el 2026-10-17); `null` si no se ha
     * comprobado. Es sólo el ejemplo del campo: el que vale es el que se
     * escribe y responde a «Probar».
     */
    public function modelExample(): ?string
    {
        return match ($this) {
            self::Gemini => 'gemini-3.8-flash',
            self::Mistral => 'mistral-medium-3-5',
            self::Xai => 'grok-4.7',
            self::OpenAi => null,
            self::MiniMax => 'MiniMax-M3.1-Flash',
        };
    }

    /**
     * @return class-string<AiImageProvider>
     */
    public function imageConnector(): string
    {
        return match ($this) {
            self::Gemini => GeminiImageProvider::class,
            self::Mistral => MistralImageProvider::class,
            self::Xai => XaiImageProvider::class,
            self::OpenAi => OpenAiImageProvider::class,
            self::MiniMax => MiniMaxImageProvider::class,
        };
    }

    /**
     * Dónde se ven los modelos de imagen.
     */
    public function imageModelsUrl(): string
    {
        return match ($this) {
            self::Gemini => 'https://ai.google.dev/gemini-api/docs/image-generation',
            self::Mistral => 'https://docs.mistral.ai/studio-api/agents/agent-tools/image_generation',
            self::Xai => 'https://docs.x.ai/developers/model-capabilities/images/generation',
            self::OpenAi => 'https://developers.openai.com/api/docs/guides/image-generation',
            self::MiniMax => 'https://platform.minimaxi.com/document/image_generation',
        };
    }

    /**
     * Un modelo de imagen que existía al escribir esto; `null` si no se ha
     * podido probar. Gemini y Mistral, con una llamada real el 2026-10-17; en
     * Mistral es el de texto, que maneja su herramienta de imágenes. Grok, de
     * su página de modelos (la cuenta no tenía saldo para probarlo).
     */
    public function imageModelExample(): ?string
    {
        return match ($this) {
            self::Gemini => 'gemini-nano-banana-2.1',
            self::Mistral => 'mistral-medium-3-5',
            self::Xai => 'grok-imagine-image-2.0',
            self::OpenAi => null,
            self::MiniMax => 'image-01',
        };
    }

    /**
     * Cuántas imágenes devuelve una petición: Gemini y Mistral, una; para más
     * se hacen varias a la vez.
     */
    public function maxImagesPerRequest(): int
    {
        return match ($this) {
            self::Gemini, self::Mistral => 1,
            self::MiniMax => 9,
            self::Xai, self::OpenAi => 10,
        };
    }

    /**
     * Si sabe mejorar una imagen que se le manda. La herramienta de Mistral
     * sólo genera.
     */
    public function supportsImageEditing(): bool
    {
        return $this !== self::Mistral;
    }

    /**
     * La calidad que se elige en cada petición de imagen, con su texto: en
     * Gemini y Grok, el tamaño; en OpenAI, su `quality`. Mistral no tiene.
     *
     * @return array<string, string>
     */
    public function imageQualities(): array
    {
        return match ($this) {
            self::Gemini => ['1K' => 'Normal (1K, más barata)', '2K' => 'Alta (2K)'],
            self::Xai => ['1k' => 'Normal (1k, más barata)', '2k' => 'Alta (2k)'],
            self::OpenAi => ['low' => 'Baja (más barata)', 'medium' => 'Media', 'high' => 'Alta (más cara)'],
            self::Mistral, self::MiniMax => [],
        };
    }

    /**
     * La que sale elegida la primera vez: una portada se ve a 1600 px, así que
     * la alta donde la hay.
     */
    public function defaultImageQuality(): ?string
    {
        return match ($this) {
            self::Gemini => '2K',
            self::Xai => '2k',
            self::OpenAi => 'medium',
            self::Mistral, self::MiniMax => null,
        };
    }

    /**
     * Dónde se recarga el saldo, para el mensaje de «sin saldo».
     */
    public function billingPlace(): string
    {
        return match ($this) {
            self::Gemini => 'Google AI Studio (https://ai.studio/projects)',
            self::Mistral => 'la consola de Mistral (https://console.mistral.ai)',
            self::Xai => 'la consola de xAI (https://console.x.ai)',
            self::OpenAi => 'la plataforma de OpenAI (https://platform.openai.com/settings/organization/billing)',
            self::MiniMax => 'la plataforma de MiniMax (https://platform.minimaxi.com)',
        };
    }

    /**
     * Lo que se puede elegir en el modal para cada petición. Mistral sólo
     * razona o no (`reasoning_effort` `high` o `none`; `mistral-medium-3-5`
     * rechaza `low` con un 400, probado el 2026-10-17); el resto, cuánto.
     *
     * @return list<AiReasoningLevelEnum>
     */
    public function reasoningLevels(): array
    {
        return $this === self::Mistral
            ? [AiReasoningLevelEnum::None, AiReasoningLevelEnum::High]
            : [AiReasoningLevelEnum::Low, AiReasoningLevelEnum::Medium, AiReasoningLevelEnum::High];
    }

    /**
     * Los niveles del modal, con su texto.
     *
     * @return array<string, string>
     */
    public function reasoningOptions(): array
    {
        $options = [];

        foreach ($this->reasoningLevels() as $level) {
            $options[$level->value] = $this === self::Mistral && $level === AiReasoningLevelEnum::High
                ? 'Razonar (más lento, más cuidado)'
                : $level->label();
        }

        return $options;
    }

    /**
     * El que sale elegido la primera vez.
     */
    public function defaultReasoningLevel(): AiReasoningLevelEnum
    {
        return $this === self::Mistral ? AiReasoningLevelEnum::High : AiReasoningLevelEnum::default();
    }

    /**
     * Si la API puede mandar el razonamiento mientras piensa. En Mistral no
     * se pide aparte: llega con el nivel de razonamiento.
     */
    public function canSummarizeReasoning(): bool
    {
        return $this !== self::Mistral;
    }

    /**
     * Lo que se marca al dar de alta el proveedor. xAI sólo resume el
     * razonamiento de algunos modelos, y con los demás la petición podría
     * fallar.
     */
    public function summarizesReasoningByDefault(): bool
    {
        return in_array($this, [self::Gemini, self::OpenAi, self::MiniMax], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->fullLabel();
        }

        return $options;
    }
}
