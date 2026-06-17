<?php

namespace App\Filament\Admin\Resources\Printers\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PrinterStackRelationManager extends RelationManager
{
    protected static string $relationship = 'printStack';

    protected static ?string $title = 'Cola de impresión';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('note')
                ->maxLength(511)->label('Nota'),
            Textarea::make('content')
                ->rows(6)->columnSpanFull()->label('Contenido'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('note')->limit(60)->label('Nota'),
                TextColumn::make('user.name')->label('Usuario'),
                TextColumn::make('created_at')->dateTime('d/m/Y H:i')
                    ->sortable()->label('Fecha'),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }
}
