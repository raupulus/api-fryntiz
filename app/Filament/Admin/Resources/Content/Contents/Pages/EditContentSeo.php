<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\ContentSectionPage;
use App\Filament\Components\ImageCropperUpload;
use App\Filament\Concerns\HasImageFileUpload;
use App\Models\Content\Content;
use App\Services\Content\ContentSeoService;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * «SEO» del contenido (E5 de la auditoría de contenidos; F7 del plan del
 * 2026-09-24): lo que ven los buscadores y la tarjeta al compartir en redes.
 * Los datos van a `content_seo` (`ContentSeoService::upsert()`); el panel no
 * tenía dónde rellenarlos.
 */
class EditContentSeo extends EditRecord
{
    use ContentSectionPage;
    use HasImageFileUpload;

    /**
     * Donde los buscadores y las redes suelen cortar. Se avisa, no se impide.
     */
    public const DESCRIPTION_LIMIT = 160;

    public const TITLE_LIMIT = 60;

    private const FIELDS = [
        'description', 'keywords', 'robots', 'revisit_after', 'distribution',
        'og_title', 'og_type', 'twitter_card', 'twitter_creator', 'image_id', 'image_alt',
    ];

    /**
     * Para un contenido que aún no tiene SEO: los valores de la base, salvo el
     * tipo, que para un contenido es «artículo».
     */
    private const DEFAULTS = [
        'robots' => 'index, follow',
        'revisit_after' => '7 days',
        'distribution' => 'global',
        'og_type' => 'article',
        'twitter_card' => 'summary',
    ];

    protected static string $resource = ContentResource::class;

    protected static ?string $navigationLabel = 'SEO';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMagnifyingGlass;

    public static bool $formActionsAreSticky = true;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Buscadores')
                ->description('Lo que enseñan Google y los demás buscadores.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Textarea::make('description')->label('Descripción')
                        ->rows(3)->maxLength(255)->columnSpanFull()
                        ->live(debounce: 400)
                        ->hint(fn (?string $state): string => self::counter($state, self::DESCRIPTION_LIMIT))
                        ->hintColor(fn (?string $state): string => mb_strlen((string) $state) > self::DESCRIPTION_LIMIT ? 'warning' : 'gray')
                        ->helperText('Los buscadores la cortan hacia los '.self::DESCRIPTION_LIMIT.' caracteres. Vacía, la web usa el extracto.'),
                    TextInput::make('keywords')->label('Palabras clave')
                        ->maxLength(511)->columnSpanFull()
                        ->helperText('Separadas por comas.'),
                    Select::make('robots')->label('Indexación')
                        ->options([
                            'index, follow' => 'Indexar y seguir enlaces (lo normal)',
                            'noindex, follow' => 'No indexar, pero seguir enlaces',
                            'index, nofollow' => 'Indexar, sin seguir enlaces',
                            'noindex, nofollow' => 'Ni indexar ni seguir enlaces',
                        ])
                        ->selectablePlaceholder(false),
                    Select::make('revisit_after')->label('Volver a pasar al cabo de')
                        ->options([
                            '1 day' => '1 día',
                            '3 days' => '3 días',
                            '7 days' => '7 días',
                            '15 days' => '15 días',
                            '30 days' => '30 días',
                        ])
                        ->helperText('Una sugerencia para los rastreadores: no todos la siguen.'),
                    Select::make('distribution')->label('Alcance')
                        ->options(['global' => 'Global (recomendado)', 'local' => 'Local', 'ui' => 'Interfaz'])
                        ->required()
                        ->selectablePlaceholder(false),
                ]),

            Section::make('Redes sociales')
                ->description('La tarjeta que se ve al compartir el enlace.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    TextInput::make('og_title')->label('Título al compartir')
                        ->maxLength(255)->columnSpanFull()
                        ->placeholder(fn (): string => (string) $this->contentRecord()->title)
                        ->live(debounce: 400)
                        ->hint(fn (?string $state): string => self::counter($state, self::TITLE_LIMIT))
                        ->hintColor(fn (?string $state): string => mb_strlen((string) $state) > self::TITLE_LIMIT ? 'warning' : 'gray')
                        ->helperText('Se corta hacia los '.self::TITLE_LIMIT.' caracteres. Vacío, se usa el título del contenido.'),
                    Select::make('og_type')->label('Tipo')
                        ->options([
                            'article' => 'Artículo',
                            'website' => 'Web',
                            'profile' => 'Perfil',
                            'video' => 'Vídeo',
                            'music' => 'Música',
                            'book' => 'Libro',
                        ])
                        ->required()
                        ->selectablePlaceholder(false),
                    Select::make('twitter_card')->label('Tarjeta en X')
                        ->options([
                            'summary' => 'Resumen con imagen pequeña',
                            'summary_large_image' => 'Resumen con imagen grande',
                        ])
                        ->selectablePlaceholder(false),
                    TextInput::make('twitter_creator')->label('Usuario del autor en X')
                        ->prefix('@')->maxLength(255),
                    ImageCropperUpload::makeImage('image_id', 'imagen para redes')
                        ->asFileRecord()
                        ->storeFiles(false)
                        ->socialCard()
                        ->directory('contents')
                        ->columnSpanFull()
                        ->label('Imagen para redes')
                        ->helperText('Se recorta a 1200 × 630, el tamaño que usan las redes. Se guarda en WebP.'),
                    TextInput::make('image_alt')->label('Texto alternativo de la imagen')
                        ->maxLength(255)->columnSpanFull()
                        ->helperText('Lo que leen los lectores de pantalla y los buscadores en lugar de la imagen.'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [$this->saveOnTopAction(), $this->previewAction()];
    }

    /**
     * El formulario es del SEO, no de las columnas del contenido.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $seo = $this->contentRecord()->seo;

        return $seo === null ? self::DEFAULTS : Arr::only($seo->attributesToArray(), self::FIELDS);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Content) {
            return $record;
        }

        // La imagen, en WebP como las demás de los contenidos (C1).
        $data = $this->resolveImageUpload($data, 'image_id', 'contents', webpOriginal: true);

        app(ContentSeoService::class)->upsert($record, Arr::only($data, self::FIELDS));

        return $record;
    }

    private static function counter(?string $text, int $limit): string
    {
        return mb_strlen((string) $text).' / '.$limit;
    }
}
