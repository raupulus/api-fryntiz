<?php

declare(strict_types=1);

namespace App\Services\Content;

/**
 * Comprueba que cada bloque de Editor.js trae su dato principal antes de
 * guardar una página.
 *
 * Las plantillas aguantan datos incompletos (ver `TextFormatParseHelper`),
 * pero un bloque sin lo que lo define —una imagen sin fichero, un vídeo sin
 * dirección— no tiene nada que enseñar: guardarlo dejaría un hueco en la web
 * sin que nadie se entere. Se avisa antes y no se guarda nada.
 *
 * Un párrafo vacío no es un error: es un salto entre bloques.
 */
class ContentBlockValidator
{
    /**
     * Tipos que el editor sabe pintar (`TextFormatParseHelper::arrayToHtml()`).
     */
    public const KNOWN_TYPES = [
        'paragraph', 'header', 'list', 'checklist', 'quote', 'delimiter', 'table', 'embed',
        'raw', 'warning', 'image', 'attaches', 'linkTool', 'alert', 'code',
    ];

    /**
     * Nombre de cada tipo en los mensajes.
     */
    private const LABELS = [
        'paragraph' => 'párrafo',
        'header' => 'título',
        'list' => 'lista',
        'checklist' => 'lista de tareas',
        'quote' => 'cita',
        'delimiter' => 'separador',
        'table' => 'tabla',
        'embed' => 'vídeo',
        'raw' => 'HTML',
        'warning' => 'aviso',
        'image' => 'imagen',
        'attaches' => 'adjunto',
        'linkTool' => 'tarjeta de enlace',
        'alert' => 'alerta',
        'code' => 'código',
    ];

    /**
     * Problemas de los bloques, uno por mensaje: «Bloque 7 (imagen): no tiene
     * fichero. Quítalo o vuelve a subir la imagen». Vacío si todo está bien.
     *
     * @param  array<mixed>  $blocks
     * @return list<string>
     */
    public function errors(array $blocks): array
    {
        $errors = [];

        foreach (array_values($blocks) as $index => $block) {
            $problem = $this->problem($block);

            if ($problem !== null) {
                $errors[] = sprintf('Bloque %d (%s): %s', $index + 1, $this->label($block), $problem);
            }
        }

        return $errors;
    }

    /**
     * Nombre legible del tipo de un bloque.
     */
    public function label(mixed $block): string
    {
        $type = is_array($block) ? ($block['type'] ?? null) : null;

        return is_string($type) ? (self::LABELS[$type] ?? $type) : 'sin tipo';
    }

    private function problem(mixed $block): ?string
    {
        if (! is_array($block) || ! is_string($block['type'] ?? null)) {
            return 'no es un bloque de Editor.js (le falta el tipo).';
        }

        $type = $block['type'];

        if (! in_array($type, self::KNOWN_TYPES, true)) {
            return "es de un tipo que el editor no conoce («{$type}») y no saldría en la web. Quítalo.";
        }

        $data = $block['data'] ?? null;

        if (! is_array($data)) {
            return $type === 'delimiter' ? null : 'no tiene datos. Quítalo o vuelve a crearlo.';
        }

        return match ($type) {
            'image' => $this->filled($data['file']['url'] ?? null) ? null : 'no tiene fichero. Quítalo o vuelve a subir la imagen.',
            'attaches' => $this->filled($data['file']['url'] ?? null) ? null : 'no tiene fichero. Quítalo o vuelve a subir el fichero.',
            'embed' => $this->filled($data['embed'] ?? $data['source'] ?? null) ? null : 'no tiene la dirección del vídeo. Quítalo o vuelve a pegar el enlace.',
            'linkTool' => $this->filled($data['link'] ?? null) ? null : 'no tiene enlace. Quítalo o vuelve a pegar la dirección.',
            'table' => $this->hasRows($data['content'] ?? null) ? null : 'no tiene filas. Quítala o añade alguna.',
            'list', 'checklist' => is_array($data['items'] ?? null) && $data['items'] !== [] ? null : 'no tiene elementos. Quítala o escribe alguno.',
            'code' => $this->filled($data['code'] ?? null) ? null : 'no tiene código. Quítalo o escribe alguno.',
            'header' => $this->filled(strip_tags((string) (is_string($data['text'] ?? null) ? $data['text'] : ''))) ? null : 'no tiene texto. Quítalo o escribe el título.',
            default => null,
        };
    }

    private function filled(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function hasRows(mixed $rows): bool
    {
        return is_array($rows) && array_filter($rows, fn ($row): bool => is_array($row) && $row !== []) !== [];
    }
}
