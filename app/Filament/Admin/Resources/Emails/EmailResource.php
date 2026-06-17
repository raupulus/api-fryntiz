<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Emails;

use App\Filament\Admin\Resources\Emails\Pages\CreateEmail;
use App\Filament\Admin\Resources\Emails\Pages\EditEmail;
use App\Filament\Admin\Resources\Emails\Pages\ListEmails;
use App\Filament\Admin\Resources\Emails\Pages\ViewEmail;
use App\Mail\RetryEmailMessage;
use App\Models\Email;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Mail;

/**
 * Resource Filament para gestionar emails del formulario de contacto.
 */
class EmailResource extends Resource
{
    protected static ?string $model = Email::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|\UnitEnum|null $navigationGroup = 'Módulos';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Email';

    protected static ?string $pluralModelLabel = 'Emails';

    protected static ?string $recordTitleAttribute = 'subject';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mensaje')->columns(2)->schema([
                Select::make('user_id')
                    ->relationship('user', 'name')->searchable()->nullable()->label('Usuario'),
                Select::make('language_id')
                    ->relationship('language', 'name')->nullable()->label('Idioma'),
                TextInput::make('email')->email()->required()->maxLength(511)->label('Destinatario'),
                TextInput::make('subject')->required()->maxLength(511)->label('Asunto'),
                Textarea::make('message')->required()->rows(10)
                    ->columnSpanFull()->label('Mensaje'),
                Toggle::make('privacity')->label('Acepta privacidad'),
                Toggle::make('contactme')->label('Permite contacto'),
                TextInput::make('priority')->numeric()->minValue(0)->maxValue(10)
                    ->default(0)->label('Prioridad'),
            ]),

            Section::make('Metadatos del cliente')->columns(2)->collapsed()->schema([
                TextInput::make('server_ip')->maxLength(255)->label('IP servidor'),
                TextInput::make('client_ip')->maxLength(255)->label('IP cliente'),
                TextInput::make('client_user_agent')->maxLength(255)->label('User agent'),
                TextInput::make('client_referer')->maxLength(255)->label('Referer'),
                TextInput::make('app_name')->maxLength(511)->label('App origen'),
                TextInput::make('app_domain')->maxLength(255)->label('Dominio app'),
                KeyValue::make('client_accept_language')->label('Idiomas cliente'),
                KeyValue::make('attributes')->label('Atributos extra'),
            ]),

            Section::make('Estado de envío')->columns(3)->schema([
                Toggle::make('send')->label('Enviado'),
                TextInput::make('attempts')->numeric()->minValue(0)->default(0)->label('Intentos'),
                DateTimePicker::make('sent_at')->label('Enviado el'),
                TextInput::make('error_code')->numeric()->label('Cód. error'),
                DateTimePicker::make('error_at')->label('Error en'),
                Textarea::make('error_message')->rows(2)
                    ->columnSpanFull()->label('Mensaje de error'),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Resumen')->columns(2)->schema([
                TextEntry::make('email')->copyable()->label('Destinatario'),
                TextEntry::make('subject')->label('Asunto'),
                TextEntry::make('language.name')->label('Idioma'),
                IconEntry::make('send')->boolean()->label('Enviado'),
                TextEntry::make('sent_at')->dateTime()->label('Enviado el'),
                TextEntry::make('attempts')->label('Intentos'),
            ])->columnSpanFull(),
            Section::make('Cuerpo del mensaje')->schema([
                TextEntry::make('message')->html()->columnSpanFull()->label(''),
            ])->columnSpanFull(),
            Section::make('Cliente')->columns(2)->collapsed()->schema([
                TextEntry::make('client_ip')->label('IP'),
                TextEntry::make('client_user_agent')->label('User Agent'),
                TextEntry::make('client_referer')->label('Referer'),
                TextEntry::make('captcha_score')->label('Captcha Score'),
                TextEntry::make('app_name')->label('App'),
                TextEntry::make('app_domain')->label('Dominio'),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IconColumn::make('send')->boolean()->label('Env.'),
                TextColumn::make('email')->searchable()->copyable()->limit(40)->label('Destinatario'),
                TextColumn::make('subject')->searchable()->limit(60)->label('Asunto'),
                TextColumn::make('language.name')->badge()->label('Idioma')->toggleable(),
                TextColumn::make('priority')->sortable()->badge()
                    ->color(fn ($state) => $state >= 5 ? 'danger' : ($state >= 3 ? 'warning' : 'gray'))
                    ->label('Prio'),
                TextColumn::make('attempts')->sortable()->toggleable()->label('Int.'),
                TextColumn::make('sent_at')->dateTime('d/m/Y H:i')->sortable()->toggleable()->label('Enviado'),
                TextColumn::make('error_code')->toggleable(isToggledHiddenByDefault: true)->label('Err'),
                TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable()->label('Recibido'),
            ])
            ->filters([
                TernaryFilter::make('send')->label('Enviado'),
                SelectFilter::make('language_id')->relationship('language', 'name')->label('Idioma'),
                Filter::make('high_priority')
                    ->query(fn ($q) => $q->where('priority', '>=', 5))
                    ->label('Alta prioridad'),
                Filter::make('with_errors')
                    ->query(fn ($q) => $q->whereNotNull('error_code'))
                    ->label('Con errores'),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('retry')
                    ->icon('heroicon-o-arrow-path')->color('warning')
                    ->visible(fn (Email $r) => ! $r->send)
                    ->requiresConfirmation()
                    ->action(function (Email $record) {
                        try {
                            Mail::to($record->email)->send(new RetryEmailMessage($record));
                            $record->update([
                                'send' => true,
                                'sent_at' => now(),
                                'attempts' => $record->attempts + 1,
                                'error_code' => null,
                                'error_message' => null,
                            ]);
                            Notification::make()->title('Email reenviado')->success()->send();
                        } catch (\Throwable $e) {
                            $record->update([
                                'attempts' => $record->attempts + 1,
                                'error_code' => 500,
                                'error_at' => now(),
                                'error_message' => $e->getMessage(),
                            ]);
                            Notification::make()->title('Error al reenviar')->danger()->body($e->getMessage())->send();
                        }
                    })->label('Reenviar'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_sent_bulk')
                        ->icon('heroicon-o-check')->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['send' => true, 'sent_at' => now()]))
                        ->label('Marcar enviados'),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmails::route('/'),
            'create' => CreateEmail::route('/create'),
            'view' => ViewEmail::route('/{record}'),
            'edit' => EditEmail::route('/{record}/edit'),
        ];
    }
}
