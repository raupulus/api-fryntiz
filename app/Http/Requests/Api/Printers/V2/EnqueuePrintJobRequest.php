<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Printers\V2;

use App\Enums\PrintJobFormatEnum;
use App\Http\Requests\Api\BaseFormRequest;
use App\Models\Printer;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validación para encolar un nuevo trabajo de impresión.
 */
class EnqueuePrintJobRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string'],
            'format' => ['nullable', 'string', Rule::enum(PrintJobFormatEnum::class)],
            'note' => ['nullable', 'string', 'max:255'],
            'priority' => ['nullable', 'integer', 'between:-100,100'],
            'is_favorite' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'El contenido o payload a imprimir es obligatorio.',
            'content.string' => 'El contenido debe ser una cadena de texto o Base64.',
            'format.enum' => 'El formato especificado no es válido.',
            'note.max' => 'La nota no puede superar los 255 caracteres.',
            'priority.between' => 'La prioridad debe estar comprendida entre -100 y 100.',
            'is_favorite.boolean' => 'El campo favorito debe ser un valor booleano.',
        ];
    }

    /**
     * Validaciones contextuales respecto a la impresora de destino.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            /** @var Printer|int|string|null $printer */
            $printer = $this->route('printer');

            if (! $printer instanceof Printer) {
                $printer = Printer::find($printer);
            }

            if (! $printer) {
                return;
            }

            if (! $printer->is_active) {
                $v->errors()->add('printer', 'La impresora seleccionada no está activa para recibir nuevos trabajos.');
            }

            $format = $this->input('format');
            if ($format !== null && ! $printer->supportsFormat((string) $format)) {
                $supported = is_array($printer->supported_formats) ? implode(', ', $printer->supported_formats) : '';
                $v->errors()->add('format', "El formato '{$format}' no está soportado por esta impresora. Formatos permitidos: {$supported}.");
            }

            $content = (string) $this->input('content', '');
            $maxBytes = $printer->max_payload_kb * 1024;
            if (strlen($content) > $maxBytes) {
                $v->errors()->add('content', "El tamaño del contenido ({$this->formatBytes(strlen($content))}) excede el límite máximo permitido por la impresora ({$printer->max_payload_kb} KB).");
            }
        });
    }

    private function formatBytes(int $bytes): string
    {
        return round($bytes / 1024, 2).' KB';
    }
}
