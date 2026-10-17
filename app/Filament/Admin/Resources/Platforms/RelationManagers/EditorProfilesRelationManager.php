<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Platforms\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rules\Unique;

/**
 * Los perfiles de editor del asistente IA en la plataforma
 * (`platform_editor_profiles`): cómo se escribe cada tipo de entrada (una
 * guía, una noticia, un blog…). Se eligen a mano en cada entrada, y el del
 * perfil sustituye a la instrucción de la plataforma.
 */
class EditorProfilesRelationManager extends RelationManager
{
    protected static string $relationship = 'editorProfiles';

    protected static ?string $title = 'Perfiles de editor';

    protected static ?string $modelLabel = 'perfil de editor';

    protected static ?string $pluralModelLabel = 'perfiles de editor';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nombre')
                ->required()->maxLength(255)
                ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule): Unique => $rule->where('platform_id', $this->getOwnerRecord()->getKey()))
                ->helperText('El que se elige en la entrada, p. ej. «Guía», «Noticia» o «Blog».')
                ->columnSpanFull(),
            Textarea::make('system_instruction')->label('Instrucción editorial')
                ->required()->rows(10)->autosize()->maxLength(20000)
                ->helperText('Sustituye a la de la plataforma en las entradas con este perfil: escribe aquí también la voz y el tono, no sólo el patrón. El formato de la respuesta y las marcas de los bloques los añade siempre el asistente.')
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount('contents'))
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('system_instruction')->label('Instrucción')->limit(80)->wrap(),
                TextColumn::make('contents_count')->label('Entradas')
                    ->tooltip('Las que lo tienen elegido.')
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->headerActions([
                CreateAction::make()->label('Añadir perfil'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('Las entradas que lo tengan elegido pasan a usar la instrucción de la plataforma.'),
            ]);
    }
}
