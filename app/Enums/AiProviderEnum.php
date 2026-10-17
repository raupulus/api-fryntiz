<?php

declare(strict_types=1);

namespace App\Enums;

use App\Services\Ai\AiTextProvider;
use App\Services\Ai\Providers\GeminiTextProvider;
use App\Services\Ai\Providers\MistralTextProvider;
use App\Services\Ai\Providers\ResponsesTextProvider;

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
        };
    }

    public function baseUrl(): string
    {
        return match ($this) {
            self::Gemini => 'https://generativelanguage.googleapis.com/v1beta',
            self::Mistral => 'https://api.mistral.ai/v1',
            self::Xai => 'https://api.x.ai/v1',
            self::OpenAi => 'https://api.openai.com/v1',
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
        };
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
        return in_array($this, [self::Gemini, self::OpenAi], true);
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
