<?php

declare(strict_types=1);

namespace App\Services\Cv;

use App\Enums\CurriculumVisibilityEnum;
use App\Models\CV\Curriculum;
use App\Models\CV\CurriculumBaseSection;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Un currículum preparado para pintarse como documento.
 *
 * La vista web (`cv/show`) y el PDF (`cv/pdf`) comparten la maquetación del CV
 * de 2024: columna principal a la izquierda y barra lateral azul a la derecha.
 * Ordenar las secciones, formatear los periodos, partir las descripciones en
 * viñetas y decidir qué cabe en la barra lateral se hace una sola vez aquí, y
 * las dos vistas se limitan a pintar lo que reciben. Así lo que se previsualiza
 * en la web es lo mismo que se descarga.
 *
 * Convención de fechas (las columnas son timestamps, no hay campo de precisión):
 * un periodo que empieza un 1 de enero y termina un 31 de diciembre se muestra
 * sólo con años («2007 – 2009»); cualquier otro, con mes y año («12/2018 –
 * Actualidad»). Una fecha suelta que cae en 31 de diciembre se muestra como año.
 *
 * @phpstan-type Block array{type: 'text'|'list', text?: string, items?: list<string>}
 * @phpstan-type Entry array{title: string, subtitle: ?string, position: ?string, note: ?string, period: ?string, blocks: list<Block>, text: string, keep_together: bool, url: ?string, info_url: ?string, repository: ?string, meta: list<string>, credential_url: ?string}
 */
final class CurriculumDocument
{
    /**
     * Caracteres que caben en una línea de texto de la barra lateral del PDF.
     */
    private const SIDEBAR_CHARS_PER_LINE = 42;

    /**
     * Líneas de texto que caben en la barra lateral de la primera página, ya
     * descontados el logo y el bloque inferior (QR y enlaces).
     *
     * La barra sólo existe en la primera página (DomPDF no parte una columna
     * entre páginas), así que un bloque que no cabe entero pasa a la columna
     * principal en vez de cortarse.
     */
    private const SIDEBAR_LINE_BUDGET = 46;

    /**
     * Líneas que ocupa el título de un bloque de la barra lateral.
     */
    private const SIDEBAR_HEADING_LINES = 3;

    /**
     * Hasta cuántos caracteres de descripción una entrada no se parte entre páginas.
     */
    private const KEEP_TOGETHER_CHARS = 450;

    /**
     * Orden en que se intenta colocar cada bloque en la barra lateral.
     */
    private const SIDEBAR_CANDIDATES = ['profile', 'skills', 'hobbies', 'repositories'];

    /**
     * @var array<string, bool>|null
     */
    private ?array $sidebar = null;

    public function __construct(public readonly Curriculum $cv)
    {
        $cv->loadMissing(array_merge(['user', 'image'], CurriculumService::SECTIONS));
    }

    public static function for(Curriculum $cv): self
    {
        return new self($cv);
    }

    /**
     * Nombre de la persona: el del usuario dueño del currículum.
     */
    public function name(): string
    {
        return (string) ($this->cv->user->full_name ?? '');
    }

    /**
     * Titular bajo el nombre: el título del currículum.
     */
    public function headline(): string
    {
        return (string) $this->cv->title;
    }

    /**
     * Contacto público (config/cv.php), nunca el email de acceso del usuario.
     *
     * @return array{email: ?string, location: ?string, website: ?string, linkedin: ?string, github: ?string, details: list<string>}
     */
    public function contact(): array
    {
        $contact = (array) config('cv.contact', []);

        return [
            'email' => $contact['email'] ?? null,
            'location' => $contact['location'] ?? null,
            'website' => $contact['website'] ?? null,
            'linkedin' => $contact['linkedin'] ?? null,
            'github' => $contact['github'] ?? null,
            'details' => array_values((array) config('cv.details', [])),
        ];
    }

    /**
     * La presentación, en párrafos.
     *
     * @return list<Block>
     */
    public function profile(): array
    {
        return self::blocks($this->cv->presentation);
    }

    /**
     * Experiencia profesional: acreditada, autónomo, no acreditada y prácticas,
     * mezcladas en una sola lista con lo más reciente primero.
     *
     * @return list<Entry>
     */
    public function experience(): array
    {
        return $this->timeline(
            $this->cv->experienceAccredited
                ->concat($this->cv->experienceSelfEmployed)
                ->concat($this->cv->experienceNoAccredited)
                ->concat($this->cv->experienceAdditional)
        );
    }

    /**
     * Experiencia fuera del sector (hostelería, etc.), aparte.
     *
     * @return list<Entry>
     */
    public function otherExperience(): array
    {
        return $this->timeline($this->cv->experienceOther);
    }

    /**
     * Formación reglada.
     *
     * @return list<Entry>
     */
    public function education(): array
    {
        return $this->timeline($this->cv->academicTraining);
    }

    /**
     * Formación complementaria presencial.
     *
     * @return list<Entry>
     */
    public function complementary(): array
    {
        return $this->timeline($this->cv->academicComplementary);
    }

    /**
     * Cursos y certificaciones online, por fecha de expedición.
     *
     * @return list<Entry>
     */
    public function certifications(): array
    {
        return $this->cv->academicComplementaryOnline
            ->sortBy([
                fn ($a, $b) => $this->timestamp($b->expedition_at ?? $b->end_at ?? $b->start_at)
                    <=> $this->timestamp($a->expedition_at ?? $a->end_at ?? $a->start_at),
                fn ($a, $b) => $a->id <=> $b->id,
            ])
            ->map(fn (CurriculumBaseSection $row) => $this->entry($row))
            ->values()
            ->all();
    }

    /**
     * Habilidades: cada fila es un grupo («Backend») con su lista en la descripción.
     *
     * @return list<array{name: string, text: ?string, level: ?int}>
     */
    public function skills(): array
    {
        return $this->ordered($this->cv->skills)
            ->map(fn (CurriculumBaseSection $row) => [
                'name' => (string) $row->getAttribute('name'),
                'text' => filled($row->getAttribute('description')) ? trim((string) $row->getAttribute('description')) : null,
                'level' => $row->getAttribute('level') !== null ? (int) $row->getAttribute('level') : null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<Entry>
     */
    public function projects(): array
    {
        return $this->listed($this->cv->projects);
    }

    /**
     * @return list<Entry>
     */
    public function jobs(): array
    {
        return $this->listed($this->cv->jobs);
    }

    /**
     * @return list<Entry>
     */
    public function services(): array
    {
        return $this->listed($this->cv->services);
    }

    /**
     * @return list<Entry>
     */
    public function collaborations(): array
    {
        return $this->listed($this->cv->collaborations);
    }

    /**
     * @return list<Entry>
     */
    public function hobbies(): array
    {
        return $this->listed($this->cv->hobbies);
    }

    /**
     * @return list<Entry>
     */
    public function repositories(): array
    {
        return $this->listed($this->cv->repositories);
    }

    /**
     * ¿Este bloque va en la barra lateral? Si no, se pinta en la columna principal.
     */
    public function inSidebar(string $block): bool
    {
        return $this->sidebarLayout()[$block] ?? false;
    }

    /**
     * Enlace público del currículum, el que lleva el QR. Un CV privado no tiene.
     */
    public function publicUrl(): ?string
    {
        return match ($this->cv->visibility) {
            CurriculumVisibilityEnum::Public => route('cv.show', ['slug' => $this->cv->slug]),
            CurriculumVisibilityEnum::Shared => filled($this->cv->share_token)
                ? route('cv.shared.pdf', ['shareToken' => $this->cv->share_token])
                : null,
            default => null,
        };
    }

    /**
     * QR del enlace público en SVG, como data URI (sirve en el PDF y en la web).
     */
    public function qrCodeDataUri(): ?string
    {
        $url = $this->publicUrl();

        if ($url === null) {
            return null;
        }

        $svg = (new Writer(new ImageRenderer(new RendererStyle(240, 1), new SvgImageBackEnd)))->writeString($url);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Ruta en disco de la imagen del currículum para el PDF (DomPDF no descarga
     * nada por red). Sin imagen propia, el logotipo del sitio.
     */
    public function logoPath(): string
    {
        $path = $this->cv->image?->storage_path_file;

        if (filled($path) && is_readable($path)) {
            return $path;
        }

        return public_path('images/logo/logo320x320.png');
    }

    /**
     * Icono de heroicons (variante «mini», rellena) coloreado, como data URI.
     * En el PDF no hay fuente de iconos: van como imagen.
     */
    public static function icon(string $name, string $color): string
    {
        $file = base_path('vendor/blade-ui-kit/blade-heroicons/resources/svg/m-'.$name.'.svg');
        $svg = is_readable($file) ? (string) file_get_contents($file) : '';
        $svg = str_replace('currentColor', $color, $svg);

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Texto de un periodo según la convención de la cabecera de la clase.
     */
    public static function period(mixed $start, mixed $end): ?string
    {
        $from = self::date($start);
        $to = self::date($end);

        if ($from === null && $to === null) {
            return null;
        }

        if ($from === null) {
            return self::single($to);
        }

        $startsOnYear = $from->format('m-d') === '01-01';

        if ($to === null) {
            return ($startsOnYear ? $from->format('Y') : $from->format('m/Y')).' – Actualidad';
        }

        $format = ($startsOnYear && $to->format('m-d') === '12-31') ? 'Y' : 'm/Y';
        $a = $from->format($format);
        $b = $to->format($format);

        return $a === $b ? $a : $a.' – '.$b;
    }

    /**
     * Una fecha suelta (la de expedición de un curso, por ejemplo).
     */
    public static function single(mixed $value): ?string
    {
        $date = self::date($value);

        if ($date === null) {
            return null;
        }

        return $date->format('m-d') === '12-31' ? $date->format('Y') : $date->format('m/Y');
    }

    /**
     * Parte un texto en bloques: las líneas que empiezan por «- », «• » o «* »
     * son viñetas (y las seguidas se agrupan en una lista); el resto, párrafos.
     *
     * @return list<Block>
     */
    public static function blocks(?string $text): array
    {
        $blocks = [];

        foreach (preg_split('/\R/u', trim((string) $text)) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (preg_match('/^[-•*]\s+(.+)$/u', $line, $matches) === 1) {
                $last = array_key_last($blocks);

                if ($last !== null && $blocks[$last]['type'] === 'list') {
                    $blocks[$last]['items'][] = $matches[1];
                } else {
                    $blocks[] = ['type' => 'list', 'items' => [$matches[1]]];
                }

                continue;
            }

            $blocks[] = ['type' => 'text', 'text' => $line];
        }

        return $blocks;
    }

    /**
     * Reparte los bloques candidatos entre barra lateral y columna principal.
     *
     * Se recorren en orden y cada uno entra en la barra si cabe entero en lo que
     * queda de presupuesto; si no, se queda en la columna principal y se prueba
     * con el siguiente. El cálculo es una estimación por caracteres, calibrada
     * contra el PDF real.
     *
     * @return array<string, bool>
     */
    private function sidebarLayout(): array
    {
        if ($this->sidebar !== null) {
            return $this->sidebar;
        }

        // La etiqueta de cada grupo de habilidades y de cada repositorio, con su
        // margen, ocupa casi dos líneas de texto: se cuentan dos para que la
        // estimación se quede corta por el lado seguro (mejor en la columna
        // principal que tapado por el bloque del QR).
        $cost = [
            'profile' => $this->linesFor((string) $this->cv->presentation),
            'skills' => collect($this->skills())
                ->sum(fn (array $skill) => 2 + $this->linesFor((string) $skill['text'])),
            'hobbies' => collect($this->hobbies())
                ->sum(fn (array $hobby) => $this->linesFor($hobby['title']) + $this->linesFor(self::plainText($hobby['blocks']))),
            'repositories' => count($this->repositories()) * 3,
        ];

        $left = self::SIDEBAR_LINE_BUDGET;
        $layout = [];

        foreach (self::SIDEBAR_CANDIDATES as $block) {
            if ($cost[$block] === 0) {
                $layout[$block] = false;

                continue;
            }

            $needed = $cost[$block] + self::SIDEBAR_HEADING_LINES;
            $layout[$block] = $needed <= $left;

            if ($layout[$block]) {
                $left -= $needed;
            }
        }

        return $this->sidebar = $layout;
    }

    /**
     * Los bloques de una descripción, de vuelta a texto (una línea por bloque o viñeta).
     *
     * @param  list<Block>  $blocks
     */
    private static function plainText(array $blocks): string
    {
        return collect($blocks)
            ->flatMap(fn (array $block) => $block['type'] === 'list' ? ($block['items'] ?? []) : [$block['text'] ?? ''])
            ->implode("\n");
    }

    /**
     * Líneas estimadas que ocupa un texto en la barra lateral.
     */
    private function linesFor(string $text): int
    {
        $lines = 0;

        foreach (preg_split('/\R/u', trim($text)) ?: [] as $line) {
            $length = mb_strlen(trim($line));

            if ($length > 0) {
                $lines += (int) ceil($length / self::SIDEBAR_CHARS_PER_LINE);
            }
        }

        return $lines;
    }

    /**
     * Experiencia o formación: lo que sigue en curso primero, después por fecha
     * de fin y, a igualdad, por fecha de inicio (como en el CV de 2024). Lo que
     * no tiene ninguna fecha va al final: no es «en curso», es «sin fecha».
     *
     * @template TRow of CurriculumBaseSection
     *
     * @param  Collection<int, TRow>  $rows
     * @return list<Entry>
     */
    private function timeline(Collection $rows): array
    {
        $end = fn (CurriculumBaseSection $row): int => match (true) {
            blank($row->getAttribute('start_at')) && blank($row->getAttribute('end_at')) => PHP_INT_MIN,
            blank($row->getAttribute('end_at')) => PHP_INT_MAX,
            default => $this->timestamp($row->getAttribute('end_at')),
        };

        return $rows
            ->sortBy([
                fn ($a, $b) => $end($b) <=> $end($a),
                fn ($a, $b) => $this->timestamp($b->getAttribute('start_at')) <=> $this->timestamp($a->getAttribute('start_at')),
                fn ($a, $b) => $a->getKey() <=> $b->getKey(),
            ])
            ->map(fn (CurriculumBaseSection $row) => $this->entry($row))
            ->values()
            ->all();
    }

    /**
     * Secciones sin fechas: en el orden de `position` que se fija en el panel.
     *
     * @template TRow of CurriculumBaseSection
     *
     * @param  Collection<int, TRow>  $rows
     * @return list<Entry>
     */
    private function listed(Collection $rows): array
    {
        return $this->ordered($rows)
            ->map(fn (CurriculumBaseSection $row) => $this->entry($row))
            ->values()
            ->all();
    }

    /**
     * @template TRow of CurriculumBaseSection
     *
     * @param  Collection<int, TRow>  $rows
     * @return Collection<int, TRow>
     */
    private function ordered(Collection $rows): Collection
    {
        return $rows->sortBy([
            fn ($a, $b) => (int) $a->getAttribute('position') <=> (int) $b->getAttribute('position'),
            fn ($a, $b) => $a->getKey() <=> $b->getKey(),
        ]);
    }

    /**
     * Normaliza una fila de cualquier sección a la misma forma.
     *
     * @return Entry
     */
    private function entry(CurriculumBaseSection $row): array
    {
        $attributes = $row->getAttributes();
        $value = fn (string $key): ?string => filled($attributes[$key] ?? null) ? trim((string) $attributes[$key]) : null;

        $meta = array_values(array_filter([
            $value('instructor'),
            isset($attributes['hours']) ? $attributes['hours'].' h' : null,
            $value('credential_id') !== null ? 'ID '.$value('credential_id') : null,
        ]));

        $period = array_key_exists('expedition_at', $attributes) && filled($attributes['expedition_at'])
            ? self::single($attributes['expedition_at'])
            : self::period($attributes['start_at'] ?? null, $attributes['end_at'] ?? null);

        // `position` es el puesto en las tablas de experiencia, pero en el resto
        // de secciones es el orden manual (un entero): ahí el rol va en `role`.
        $isExperience = array_key_exists('company', $attributes);

        $blocks = self::blocks($value('description'));

        return [
            'title' => (string) ($value('title') ?? $value('name') ?? ''),
            'subtitle' => $value('company') ?? $value('entity'),
            'position' => $isExperience ? $value('position') : $value('role'),
            'note' => $value('note'),
            'period' => $period,
            'blocks' => $blocks,
            'text' => self::plainText($blocks),
            // Las entradas cortas no se parten entre páginas; las largas sí,
            // o dejarían media página en blanco al saltar enteras.
            'keep_together' => mb_strlen(self::plainText($blocks)) <= self::KEEP_TOGETHER_CHARS,
            'url' => $value('url'),
            'info_url' => $value('urlinfo'),
            'repository' => $value('repository'),
            'meta' => $meta,
            'credential_url' => $value('credential_url'),
        ];
    }

    private static function date(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return $value instanceof \DateTimeInterface
            ? Carbon::instance($value)
            : Carbon::parse((string) $value);
    }

    private function timestamp(mixed $value, int $whenEmpty = 0): int
    {
        return self::date($value)?->getTimestamp() ?? $whenEmpty;
    }
}
