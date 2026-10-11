<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Platforms\RelationManagers;

use App\Models\Category;
use App\Models\Platform;
use App\Models\PlatformCategory;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Las categorías que usa la plataforma (`platform_categories`).
 *
 * Es la lista de la que salen las categorías al editar un contenido de esta
 * plataforma y lo que enseña `GET /platforms/{slug}/categories` para montar un
 * menú. Se gestiona la fila de la pivote, no la categoría: éstas son globales
 * (se editan en «Categorías»). Quitar una la quita de los contenidos que la
 * tuvieran.
 */
class CategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'platformCategories';

    protected static ?string $title = 'Categorías';

    protected static ?string $modelLabel = 'categoría';

    protected static ?string $pluralModelLabel = 'categorías';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                ->with(['category.parentCategory'])
                ->withCount('contentCategories'))
            ->columns([
                TextColumn::make('category.name')->label('Categoría')
                    ->description(fn (PlatformCategory $record): ?string => $record->category->parentCategory ? 'Subcategoría de '.$record->category->parentCategory->name : null)
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereHas('category', fn (Builder $category) => $category->where('name', 'ilike', "%{$search}%"))),
                TextColumn::make('category.slug')->label('Slug')->toggleable(),
                TextColumn::make('content_categories_count')->label('Contenidos')
                    ->tooltip('Los que la tienen marcada, publicados o no.')
                    ->sortable(),
            ])
            ->defaultSort('category_id')
            ->headerActions([
                Action::make('attach')->label('Añadir categorías')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Añadir categorías a la plataforma')
                    ->schema([
                        Select::make('categories')->label('Categorías')
                            ->multiple()->searchable()->preload()->required()
                            ->options(fn (): array => $this->availableCategories())
                            ->createOptionForm([
                                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                                Select::make('parent_id')->label('Categoría padre')
                                    ->options(fn (): array => Category::query()->whereNull('parent_id')->orderBy('name')->pluck('name', 'id')->all())
                                    ->searchable()
                                    ->placeholder('Ninguna: es una categoría raíz'),
                            ])
                            ->createOptionUsing(fn (array $data): int => $this->findOrCreateCategory((string) $data['name'], $data['parent_id'] ? (int) $data['parent_id'] : null))
                            ->createOptionModalHeading('Nueva categoría')
                            ->helperText('Las que ya existen en el sistema, o una nueva con «+». Una subcategoría se muestra bajo su padre si éste también está en la plataforma.'),
                    ])
                    ->action(function (array $data): void {
                        $this->attachCategories((array) $data['categories']);

                        Notification::make()->title('Categorías añadidas')->success()->send();
                    }),
            ])
            ->recordActions([
                DeleteAction::make()->label('Quitar')
                    ->modalHeading('Quitar la categoría de la plataforma')
                    ->modalDescription(fn (PlatformCategory $record): string => $this->warning(collect([$record])))
                    ->successNotificationTitle('Categoría quitada'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('detach')->label('Quitar de la plataforma')
                        ->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                        ->modalDescription(fn (Collection $records): string => $this->warning($records))
                        ->action(function (Collection $records): void {
                            $records->each->delete();

                            Notification::make()->title('Categorías quitadas')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Sin categorías')
            ->emptyStateDescription('Añade las que usará esta plataforma: son las que podrás elegir en sus contenidos.');
    }

    /**
     * Las categorías del sistema que aún no están en la plataforma.
     *
     * @return array<int, string>
     */
    private function availableCategories(): array
    {
        return Category::query()
            ->whereNotIn('id', $this->platform()->platformCategories()->select('category_id'))
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Category $category): array => [$category->id => $category->name.($category->parent_id ? ' (subcategoría)' : '')])
            ->all();
    }

    /**
     * @param  array<int, int|string>  $categoryIds
     */
    private function attachCategories(array $categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            $this->platform()->platformCategories()->firstOrCreate(['category_id' => (int) $categoryId]);
        }
    }

    /**
     * Crea la categoría, o reutiliza la que ya existe con ese nombre o slug:
     * son únicos en toda la base. La pone en la plataforma al aceptar el
     * modal, no antes.
     */
    private function findOrCreateCategory(string $name, ?int $parentId): int
    {
        $name = trim($name);

        return (int) (Category::query()->where('name', $name)->orWhere('slug', Str::slug($name))->first()
            ?? Category::query()->create(['name' => $name, 'slug' => Str::slug($name), 'parent_id' => $parentId]))->id;
    }

    /**
     * Aviso de lo que se pierde al quitarlas: borra la fila de la pivote y la
     * clave foránea en cascada se lleva sus enlaces con los contenidos.
     *
     * @param  Collection<int, PlatformCategory>  $records
     */
    private function warning(Collection $records): string
    {
        $contents = (int) $records->sum(fn (PlatformCategory $record): int => $record->contentCategories()->count());

        return $contents === 0
            ? 'Ningún contenido la tiene marcada.'
            : "Se quitará de {$contents} enlace(s) con contenidos (también si son borradores) y no se puede deshacer.";
    }

    private function platform(): Platform
    {
        /** @var Platform */
        return $this->getOwnerRecord();
    }
}
