<?php

declare(strict_types=1);

namespace Tests\Unit\Content;

use App\Services\Content\ContentBlockValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Lo que no tiene arreglo se avisa antes de guardar, con el número y el tipo
 * del bloque (A2 de la auditoría de contenidos del 2026-09-24).
 */
class ContentBlockValidatorTest extends TestCase
{
    private function errors(array $blocks): array
    {
        return (new ContentBlockValidator)->errors($blocks);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function blocksWithoutTheirMainData(): array
    {
        return [
            'imagen sin fichero' => [['type' => 'image', 'data' => ['caption' => 'Foto']], 'Bloque 2 (imagen): no tiene fichero. Quítalo o vuelve a subir la imagen.'],
            'adjunto sin fichero' => [['type' => 'attaches', 'data' => ['title' => 'Informe']], 'Bloque 2 (adjunto): no tiene fichero.'],
            'vídeo sin dirección' => [['type' => 'embed', 'data' => ['service' => 'youtube']], 'Bloque 2 (vídeo): no tiene la dirección del vídeo.'],
            'tarjeta sin enlace' => [['type' => 'linkTool', 'data' => ['meta' => []]], 'Bloque 2 (tarjeta de enlace): no tiene enlace.'],
            'tabla sin filas' => [['type' => 'table', 'data' => ['withHeadings' => true, 'content' => []]], 'Bloque 2 (tabla): no tiene filas.'],
            'lista vacía' => [['type' => 'list', 'data' => ['style' => 'ordered', 'items' => []]], 'Bloque 2 (lista): no tiene elementos.'],
            'código vacío' => [['type' => 'code', 'data' => ['code' => '  ']], 'Bloque 2 (código): no tiene código.'],
            'título vacío' => [['type' => 'header', 'data' => ['text' => '', 'level' => 3]], 'Bloque 2 (título): no tiene texto.'],
            'tipo desconocido' => [['type' => 'carrusel', 'data' => []], 'Bloque 2 (carrusel): es de un tipo que el editor no conoce («carrusel»)'],
            'sin tipo' => [['data' => ['text' => 'x']], 'Bloque 2 (sin tipo): no es un bloque de Editor.js'],
        ];
    }

    #[Test]
    #[DataProvider('blocksWithoutTheirMainData')]
    public function a_block_without_its_main_data_is_reported_with_its_number_and_type(array $block, string $message): void
    {
        $errors = $this->errors([['type' => 'paragraph', 'data' => ['text' => 'Hola']], $block]);

        $this->assertCount(1, $errors);
        $this->assertStringStartsWith($message, $errors[0]);
    }

    #[Test]
    public function complete_blocks_and_an_empty_paragraph_pass(): void
    {
        $this->assertSame([], $this->errors([
            ['type' => 'paragraph', 'data' => ['text' => '']],
            ['type' => 'header', 'data' => ['text' => 'Título', 'level' => 3]],
            ['type' => 'image', 'data' => ['file' => ['url' => 'https://ejemplo.test/a.webp']]],
            ['type' => 'attaches', 'data' => ['file' => ['url' => 'https://ejemplo.test/a.pdf', 'name' => 'a.pdf']]],
            ['type' => 'embed', 'data' => ['embed' => 'https://www.youtube.com/embed/x']],
            ['type' => 'linkTool', 'data' => ['link' => 'https://ejemplo.test']],
            ['type' => 'table', 'data' => ['content' => [['A']]]],
            ['type' => 'list', 'data' => ['style' => 'unordered', 'items' => [['content' => 'Uno', 'meta' => [], 'items' => []]]]],
            ['type' => 'checklist', 'data' => ['items' => [['text' => 'Hecho', 'checked' => true]]]],
            ['type' => 'code', 'data' => ['code' => 'echo 1;']],
            ['type' => 'delimiter', 'data' => []],
            ['type' => 'delimiter'],
            ['type' => 'quote', 'data' => ['text' => 'Cita']],
            ['type' => 'alert', 'data' => ['message' => 'Aviso']],
            ['type' => 'warning', 'data' => ['message' => 'Ojo']],
            ['type' => 'raw', 'data' => ['html' => '<b>x</b>']],
        ]));
    }

    #[Test]
    public function the_19_real_pages_pass(): void
    {
        foreach (glob(__DIR__.'/../../Fixtures/content-pages/*.json') ?: [] as $file) {
            $blocks = json_decode((string) file_get_contents($file), true)['blocks'];

            $this->assertSame([], $this->errors($blocks), basename($file));
        }
    }
}
