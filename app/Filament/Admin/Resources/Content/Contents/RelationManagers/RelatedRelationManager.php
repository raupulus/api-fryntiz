<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\RelationManagers;

use App\Filament\Admin\Resources\Content\Contents\ContentResource;
use App\Models\Content\Content;
use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

/**
 * Contenidos relacionados. Vincula quien edita el contenido, y el selector
 * sólo ofrece contenidos que el usuario alcanza, buscando por título desde dos
 * letras y sin precargar (B4 del plan de contenidos del 2026-09-24).
 */
class RelatedRelationManager extends RelationManager
{
    protected static string $relationship = 'contentsRelated';

    protected static ?string $title = 'Contenidos relacionados';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('view', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            // La relación es auto-referenciada (Content -> Content) a través de
            // "content_related". Filament adivina el nombre inverso pluralizando
            // el modelo padre ("contents"), pero ese método no existe: la
            // relación inversa real es contentsRelatedMe().
            ->inverseRelationship('contentsRelatedMe')
            ->columns([
                TextColumn::make('title')->searchable()->label('Título'),
                TextColumn::make('platform.title')->badge()->label('Plataforma'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Vincular contenido')
                    // Vincular y desvincular sólo miran «sólo lectura» en
                    // Filament: la política va aquí.
                    ->authorize(fn (): bool => Gate::allows('update', $this->getOwnerRecord()))
                    ->recordSelect(fn (Select $select): Select => $select
                        ->searchPrompt('Escribe al menos dos letras del título')
                        ->getSearchResultsUsing(fn (string $search): array => $this->candidates($search)))
                    // El pivote se escribe sin eventos: la API tiene que enterarse.
                    ->after(fn () => Content::markChanged($this->getOwnerRecord()->getKey())),
            ])
            ->recordActions([
                DetachAction::make()
                    ->authorize(fn (): bool => Gate::allows('update', $this->getOwnerRecord()))
                    ->after(fn () => Content::markChanged($this->getOwnerRecord()->getKey())),
            ]);
    }

    /**
     * Contenidos de la misma plataforma que el usuario alcanza (los de
     * `ContentResource::getEloquentQuery()`), por título.
     *
     * @return array<int, string>
     */
    private function candidates(string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        $content = $this->getOwnerRecord();

        if (! $content instanceof Content) {
            return [];
        }

        return ContentResource::getEloquentQuery()
            ->where('platform_id', $content->platform_id)
            ->whereKeyNot($content->getKey())
            ->whereNotIn('contents.id', $content->contentsRelated()->pluck('contents.id'))
            ->where('title', 'ilike', '%'.trim($search).'%')
            ->orderBy('title')
            ->limit(50)
            ->pluck('title', 'id')
            ->all();
    }
}
