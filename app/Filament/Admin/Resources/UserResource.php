<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';
    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';
    protected static ?string $modelLabel = 'Usuario';
    protected static ?string $pluralModelLabel = 'Usuarios';

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255)->label('Nombre'),
            Forms\Components\TextInput::make('nickname')->maxLength(100)->label('Apodo'),
            Forms\Components\TextInput::make('email')->email()->required()->unique(ignoreRecord: true)->label('Email'),
            Forms\Components\Select::make('role_id')->relationship('role', 'name')->required()->label('Rol'),
            Forms\Components\TextInput::make('password')->password()->dehydrated(fn ($state) => filled($state))->label('Contraseña'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable()->sortable()->label('Nombre'),
                Tables\Columns\TextColumn::make('email')->searchable()->label('Email'),
                Tables\Columns\TextColumn::make('role.name')->label('Rol')->badge(),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable()->label('Creado'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role_id')->relationship('role', 'name')->label('Rol'),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\UserResource\Pages\ListUsers::route('/'),
            'create' => \App\Filament\Admin\Resources\UserResource\Pages\CreateUser::route('/create'),
            'edit' => \App\Filament\Admin\Resources\UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
