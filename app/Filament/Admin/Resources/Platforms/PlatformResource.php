<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Platforms;

use App\Filament\Admin\Resources\Platforms\Pages\CreatePlatform;
use App\Filament\Admin\Resources\Platforms\Pages\EditPlatform;
use App\Filament\Admin\Resources\Platforms\Pages\ListPlatforms;
use App\Filament\Components\ImageCropperUpload;
use App\Models\Platform;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PlatformResource extends Resource
{
    protected static ?string $model = Platform::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Gestión';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Plataforma';

    protected static ?string $pluralModelLabel = 'Plataformas';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Imagen principal')->schema([
                // `image_path` NO existe como columna en ninguna tabla del
                // proyecto (N232): el formulario pedía un campo que no se
                // guardaba en ningún sitio, así que la imagen se perdía al
                // guardar sin dar ningún error. La columna que sí existe, con su
                // clave foránea a `files`, es `image_id`.
                ImageCropperUpload::makeImage('image_id')
                    ->storeFiles(false)
                    ->dehydrated(fn ($state) => filled($state))
                    ->logo()
                    ->directory('platforms')
                    ->hiddenLabel()
                    ->extraAttributes(['class' => 'flex justify-center mx-auto'])
                    ->columnSpanFull(),
            ])->columnSpanFull(),

            Section::make('General')->columns(2)->schema([
                TextInput::make('title')->required()->maxLength(511)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null
                    )->label('Título'),
                TextInput::make('slug')->required()->maxLength(255)
                    ->unique(ignoreRecord: true)->rule('alpha_dash')->label('Slug'),
                Textarea::make('description')->maxLength(1023)->rows(3)
                    ->columnSpanFull()->label('Descripción'),
                Hidden::make('user_id')->default(fn () => auth()->id()),
            ])->columnSpanFull(),

            Section::make('Web y enlaces')->columns(2)->schema([
                TextInput::make('domain')->url()->maxLength(255)->label('Dominio'),
                TextInput::make('url_about')->url()->maxLength(255)->label('URL "Acerca de"'),
            ])->columnSpanFull(),

            Section::make('YouTube')->columns(2)->collapsed()->schema([
                TextInput::make('youtube_channel_id')->maxLength(64)->label('Channel ID'),
                TextInput::make('youtube_presentation_video_id')->maxLength(64)->label('Vídeo presentación'),
            ]),
            Section::make('Redes sociales')->columns(2)->collapsed()->schema([
                TextInput::make('twitter')->maxLength(255)->prefix('@')->label('Twitter'),
                TextInput::make('twitter_token')->password()->revealable()->maxLength(511)->label('Twitter token'),
                TextInput::make('mastodon')->maxLength(255)->label('Mastodon'),
                TextInput::make('mastodon_token')->password()->revealable()->maxLength(255)->label('Mastodon token'),
                TextInput::make('twitch')->maxLength(255)->label('Twitch'),
                TextInput::make('tiktok')->maxLength(255)->label('TikTok'),
                TextInput::make('instagram')->maxLength(255)->label('Instagram'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->label('ID')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')->searchable()->sortable()->label('Título'),
                TextColumn::make('slug')->searchable()->toggleable()->label('Slug'),
                TextColumn::make('domain')->url(fn ($state) => $state, true)->toggleable()->label('Dominio'),
                TextColumn::make('user.name')->toggleable()->label('Usuario'),
                TextColumn::make('created_at')->label('Creado el')->dateTime('d/m/Y')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlatforms::route('/'),
            'create' => CreatePlatform::route('/create'),
            'edit' => EditPlatform::route('/{record}/edit'),
        ];
    }
}
