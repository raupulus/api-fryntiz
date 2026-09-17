<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Printers\RelationManagers;

use App\Enums\PrintJobFormatEnum;
use App\Enums\PrintJobStatusEnum;
use App\Models\PrinterStack;
use App\Services\Printers\PrinterService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PrinterStackRelationManager extends RelationManager
{
    protected static string $relationship = 'printStack';

    protected static ?string $title = 'Cola de impresión';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('note')
                ->maxLength(255)
                ->label('Nota descriptiva'),

            Select::make('format')
                ->options(PrintJobFormatEnum::options())
                ->default(PrintJobFormatEnum::Text->value)
                ->required()
                ->label('Formato de contenido'),

            TextInput::make('priority')
                ->numeric()
                ->default(0)
                ->label('Prioridad (mayor se imprime antes)'),

            Toggle::make('is_favorite')
                ->default(false)
                ->label('Guardar como plantilla / favorito'),

            Textarea::make('content')
                ->rows(6)
                ->columnSpanFull()
                ->required()
                ->label('Contenido / Payload'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state instanceof PrintJobStatusEnum ? $state->color() : (PrintJobStatusEnum::tryFrom((string) $state)?->color() ?? 'gray'))
                    ->formatStateUsing(fn ($state) => $state instanceof PrintJobStatusEnum ? $state->label() : (PrintJobStatusEnum::tryFrom((string) $state)?->label() ?? $state))
                    ->label('Estado'),
                TextColumn::make('format')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof PrintJobFormatEnum ? $state->label() : (PrintJobFormatEnum::tryFrom((string) $state)?->label() ?? $state))
                    ->label('Formato'),
                TextColumn::make('note')->limit(35)->searchable()->label('Nota'),
                IconColumn::make('is_favorite')->boolean()->label('Fav'),
                TextColumn::make('priority')->sortable()->label('Prioridad'),
                TextColumn::make('print_count')->numeric()->sortable()->label('Impresiones'),
                TextColumn::make('attempts')->numeric()->sortable()->label('Intentos'),
                TextColumn::make('user.name')->label('Usuario')->toggleable(),
                TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable()->label('Encolado'),
                TextColumn::make('printed_at')->dateTime('d/m/Y H:i')->sortable()->toggleable(isToggledHiddenByDefault: true)->label('Impreso'),
            ])
            ->filters([
                SelectFilter::make('status')->options(PrintJobStatusEnum::options())->label('Estado'),
                SelectFilter::make('format')->options(PrintJobFormatEnum::options())->label('Formato'),
                TernaryFilter::make('is_favorite')->label('Favorito'),
            ])
            ->defaultSort('id', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['status'] = PrintJobStatusEnum::Pending;
                        $data['attempts'] = 0;
                        $data['print_count'] = 0;

                        return $data;
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('reprint')
                    ->label('Reimprimir')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->action(function (PrinterStack $record, PrinterService $service) {
                        $service->reprintJob($record, null, auth()->user());
                        Notification::make()
                            ->title('Trabajo reencolado para reimpresión')
                            ->success()
                            ->send();
                    }),
                Action::make('retry')
                    ->label('Reintentar')
                    ->icon(Heroicon::OutlinedArrowUturnLeft)
                    ->color('warning')
                    ->visible(fn (PrinterStack $record) => $record->status === PrintJobStatusEnum::Failed)
                    ->action(function (PrinterStack $record) {
                        $record->update([
                            'status' => PrintJobStatusEnum::Pending,
                            'error_message' => null,
                        ]);
                        Notification::make()
                            ->title('Trabajo restablecido a pendiente')
                            ->info()
                            ->send();
                    }),
                Action::make('cancel')
                    ->label('Cancelar')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->visible(fn (PrinterStack $record) => $record->status === PrintJobStatusEnum::Pending)
                    ->action(function (PrinterStack $record, PrinterService $service) {
                        $service->cancelJob($record);
                        Notification::make()
                            ->title('Trabajo cancelado')
                            ->warning()
                            ->send();
                    }),
                DeleteAction::make(),
            ]);
    }
}
