<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Resource Filament para usuarios.
 *
 * Reescrito en fix_10 / fase 01 para resolver el HTTP 500 producido por
 * el uso de Filament\Tables\Actions\* (inexistente en Filament v5).
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Foto de perfil')
                ->schema([
                    FileUpload::make('profile_photo_path')
                        ->image()
                        ->avatar()
                        ->imageEditor()
                        ->imageEditorAspectRatios(['1:1'])
                        ->directory('profile-photos')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->extraAttributes(['class' => 'flex justify-center mx-auto'])
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ])->columnSpanFull(),

            Section::make('Perfil')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->required()->maxLength(255)->label('Nombre'),
                    TextInput::make('surname')->maxLength(255)->label('Apellidos'),
                ])->columnSpanFull(),

            Section::make('Datos de acceso')
                ->columns(2)
                ->schema([
                    TextInput::make('email')
                        ->email()->required()->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->label('Email'),
                    TextInput::make('nickname')
                        ->maxLength(511)
                        ->unique(ignoreRecord: true)
                        ->label('Nickname'),
                    TextInput::make('password')
                        ->password()->revealable()
                        ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                        ->dehydrated(fn ($state) => filled($state))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->rule(Password::defaults())
                        ->label('Contraseña'),
                    Select::make('role_id')
                        ->relationship('role', 'display_name')
                        ->required()->preload()->searchable()
                        ->label('Rol'),
                ])->columnSpanFull(),

            Section::make('Detalles')
                ->relationship('details')
                ->columns(2)
                ->schema([
                    TextInput::make('profession')->maxLength(255)->label('Profesión'),
                    TextInput::make('web')->url()->maxLength(255)->label('Web personal'),
                ])->columnSpanFull(),

            Section::make('Notificaciones')
                ->relationship('settings')
                ->columns(3)
                ->schema([
                    Toggle::make('send_email')->default(true)->label('Permitir emails'),
                    Toggle::make('send_notification')->default(true)->label('Notificaciones'),
                    Toggle::make('send_notification_push')->default(true)->label('Push'),
                ]),

            Section::make('Verificación')
                ->columns(2)
                ->schema([
                    DateTimePicker::make('email_verified_at')->label('Email verificado'),
                ])
                ->hiddenOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('profile_photo_path')->circular()->label('Foto'),
                TextColumn::make('id')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')->searchable()->sortable()->label('Nombre'),
                TextColumn::make('nickname')->searchable()->toggleable()->label('Nick'),
                TextColumn::make('email')->searchable()->copyable()->label('Email'),
                TextColumn::make('role.display_name')->badge()->label('Rol'),
                IconColumn::make('email_verified_at')->boolean()->label('Verif.'),
                TextColumn::make('created_at')->dateTime('d/m/Y H:i')->sortable()->label('Creado'),
            ])
            ->filters([
                SelectFilter::make('role_id')->relationship('role', 'display_name')->label('Rol'),
                TrashedFilter::make(),
                TernaryFilter::make('email_verified_at')->nullable()->label('Verificado'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            UserResource\RelationManagers\SocialsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => UserResource\Pages\ListUsers::route('/'),
            'create' => UserResource\Pages\CreateUser::route('/create'),
            'edit' => UserResource\Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }
}
