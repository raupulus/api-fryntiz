<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\Pages;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\ContentSectionPage;
use App\Filament\Admin\Resources\Content\Contents\Pages\Concerns\SavesFromTheHeader;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\PlatformCategory;
use App\Models\Tag;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * «Categorías y etiquetas» (E6 de la auditoría de contenidos; F7 del plan del
 * 2026-09-24): las de la plataforma del contenido, con categoría principal,
 * subcategorías y etiquetas, y la posibilidad de crear una nueva sin salir de
 * aquí. La ficha sólo dejaba elegir tecnologías.
 *
 * Se guardan con `Content::saveCategories()` y `saveTags()` (revisadas en F5):
 * se enlaza la categoría o etiqueta **de la plataforma**, y lo que se quita
 * queda como fila borrada.
 */
class EditContentTaxonomies extends EditRecord
{
    use ContentSectionPage;
    use SavesFromTheHeader;

    protected static string $resource = ContentResource::class;

    protected static ?string $navigationLabel = 'Categorías y etiquetas';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    public static bool $formActionsAreSticky = true;

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->description(fn (): string => 'Las de la plataforma «'.($this->contentRecord()->platform->title ?? '—').'». Una nueva se añade también a la plataforma.')
                ->columns(2)
                ->columnSpanFull()
                ->schema([
                    Select::make('categories')->label('Categorías')
                        ->multiple()->searchable()->preload()
                        ->options(fn (): array => $this->categoryOptions())
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set): void {
                            // La principal tiene que ser una de las elegidas.
                            if (! in_array((int) $get('main_category_id'), array_map('intval', (array) $get('categories')), true)) {
                                $set('main_category_id', null);
                            }
                        })
                        ->createOptionForm([TextInput::make('name')->label('Nombre')->required()->maxLength(255)])
                        ->createOptionUsing(fn (array $data): int => $this->createCategory((string) $data['name']))
                        ->createOptionModalHeading('Nueva categoría'),
                    Select::make('main_category_id')->label('Categoría principal')
                        ->options(fn (Get $get): array => Arr::only($this->categoryOptions(), array_map('intval', (array) $get('categories'))))
                        ->placeholder('Ninguna')
                        ->helperText('La que representa al contenido, si tiene varias.'),
                    Select::make('subcategories')->label('Subcategorías')
                        ->multiple()->searchable()
                        ->options(fn (Get $get): array => $this->subcategoryOptions((array) $get('categories')))
                        ->helperText('Las de las categorías elegidas.')
                        ->createOptionForm(fn (Get $get): array => [
                            Select::make('parent_id')->label('Categoría')
                                ->options(Arr::only($this->categoryOptions(), array_map('intval', (array) $get('categories'))))
                                ->required(),
                            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                        ])
                        ->createOptionUsing(fn (array $data): int => $this->createCategory((string) $data['name'], (int) $data['parent_id']))
                        ->createOptionModalHeading('Nueva subcategoría'),
                    Select::make('tags')->label('Etiquetas')
                        ->multiple()->searchable()->preload()
                        ->options(fn (Get $get): array => $this->tagOptions((array) $get('tags')))
                        ->createOptionForm([TextInput::make('name')->label('Nombre')->required()->maxLength(255)])
                        ->createOptionUsing(fn (array $data): int => $this->createTag((string) $data['name']))
                        ->createOptionModalHeading('Nueva etiqueta'),
                    Select::make('technologies')->label('Tecnologías')
                        ->multiple()->searchable()->preload()
                        ->relationship('technologies', 'name')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [$this->saveOnTopAction(), $this->previewAction()];
    }

    /**
     * Lo que tiene ahora el contenido (las tecnologías las carga su campo).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $content = $this->contentRecord();

        return [
            'categories' => $content->categoriesQuery()->pluck('id')->map(fn ($id): int => (int) $id)->all(),
            // Esta consulta une tablas (la API lee `is_main` del join): la
            // columna va con su tabla.
            'subcategories' => $content->subcategoriesQuery()->distinct()->pluck('categories.id')->map(fn ($id): int => (int) $id)->all(),
            'main_category_id' => DB::table('content_categories')
                ->join('platform_categories', 'platform_categories.id', '=', 'content_categories.platform_category_id')
                ->where('content_categories.content_id', $content->id)
                ->whereNull('content_categories.deleted_at')
                ->where('content_categories.is_main', true)
                ->value('platform_categories.category_id'),
            'tags' => $content->tagsQuery()->pluck('id')->map(fn ($id): int => (int) $id)->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof Content) {
            return $record;
        }

        DB::transaction(function () use ($record, $data): void {
            $record->saveCategories(
                (array) ($data['categories'] ?? []),
                (array) ($data['subcategories'] ?? []),
                // 0: ninguna principal (null dejaría la que hubiera).
                (int) ($data['main_category_id'] ?? 0),
            );
            $record->saveTags((array) ($data['tags'] ?? []));
        });

        return $record;
    }

    /**
     * Categorías (sin padre) de la plataforma.
     *
     * @return array<int, string>
     */
    private function categoryOptions(): array
    {
        return $this->platformCategories()->whereNull('categories.parent_id')->pluck('categories.name', 'categories.id')->all();
    }

    /**
     * Subcategorías de la plataforma que cuelgan de las categorías elegidas.
     *
     * @param  array<int, mixed>  $parents
     * @return array<int, string>
     */
    private function subcategoryOptions(array $parents): array
    {
        return $this->platformCategories()
            ->whereIn('categories.parent_id', array_map('intval', $parents))
            ->pluck('categories.name', 'categories.id')
            ->all();
    }

    /**
     * @return Builder<Category>
     */
    private function platformCategories(): Builder
    {
        return Category::query()
            ->join('platform_categories', 'platform_categories.category_id', '=', 'categories.id')
            ->where('platform_categories.platform_id', $this->contentRecord()->platform_id)
            ->orderBy('categories.name')
            ->distinct();
    }

    /**
     * Etiquetas de la plataforma, más las elegidas: una recién creada no está
     * en la plataforma hasta que se guarda.
     *
     * @param  array<int, mixed>  $selected
     * @return array<int, string>
     */
    private function tagOptions(array $selected = []): array
    {
        $platformTags = Tag::query()
            ->join('platform_tags', 'platform_tags.tag_id', '=', 'tags.id')
            ->where('platform_tags.platform_id', $this->contentRecord()->platform_id)
            ->pluck('tags.name', 'tags.id')
            ->all();

        $options = $platformTags + Tag::query()->whereIn('id', array_map('intval', $selected))->pluck('name', 'id')->all();
        asort($options, SORT_NATURAL | SORT_FLAG_CASE);

        return $options;
    }

    /**
     * Crea la categoría (o reutiliza la que ya existe con ese nombre) y la
     * añade a la plataforma. Los nombres y slugs de las categorías son únicos
     * en toda la base.
     */
    private function createCategory(string $name, ?int $parentId = null): int
    {
        $name = trim($name);
        $category = Category::query()->where('name', $name)->orWhere('slug', Str::slug($name))->first()
            ?? Category::query()->create(['name' => $name, 'slug' => Str::slug($name), 'parent_id' => $parentId]);

        PlatformCategory::query()->firstOrCreate([
            'platform_id' => $this->contentRecord()->platform_id,
            'category_id' => $category->id,
        ]);

        return (int) $category->id;
    }

    /**
     * Crea la etiqueta (o reutiliza la que ya existe con ese nombre). Se añade
     * a la plataforma al guardar (`saveTags()`).
     */
    private function createTag(string $name): int
    {
        $name = trim($name);

        return (int) (Tag::query()->where('name', $name)->orWhere('slug', Str::slug($name))->first()
            ?? Tag::query()->create(['name' => $name, 'slug' => Str::slug($name)]))->id;
    }
}
