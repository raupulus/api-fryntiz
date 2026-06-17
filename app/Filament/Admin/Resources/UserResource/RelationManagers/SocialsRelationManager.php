<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\UserResource\RelationManagers;

use App\Models\SocialNetwork;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Gestor de relación para redes sociales del usuario.
 */
class SocialsRelationManager extends RelationManager
{
    protected static string $relationship = 'socials';

    protected static ?string $title = 'Redes sociales';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('social_network_id')
                ->options(SocialNetwork::query()->pluck('name', 'id'))
                ->required()->searchable()->label('Red'),
            TextInput::make('nick')->maxLength(255)->label('Nick'),
            TextInput::make('url')->url()->required()->maxLength(255)->label('URL'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('url')
            ->columns([
                TextColumn::make('socialNetwork.name')->label('Red'),
                TextColumn::make('nick')->label('Nick'),
                TextColumn::make('url')->url(fn ($state) => $state, true)->label('URL'),
            ])
            ->headerActions([
                CreateAction::make()->label('Crear Red Social')->modelLabel('Red Social'),
            ])
            ->recordActions([
                EditAction::make()->modelLabel('Red Social'),
                DeleteAction::make(),
            ]);
    }
}
