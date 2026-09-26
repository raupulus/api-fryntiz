<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents;

use App\Enums\ContentStatusEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Admin\Resources\Content\Contents\Pages\CreateContent;
use App\Filament\Admin\Resources\Content\Contents\Pages\EditContent;
use App\Filament\Admin\Resources\Content\Contents\Pages\ListContents;
use App\Filament\Components\ImageCropperUpload;
use App\Filament\Components\YoutubeVideoField;
use App\Models\Content\Content;
use App\Models\Platform;
use App\Models\User;
use App\Policies\ContentPolicy;
use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Contenido';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Contenido';

    protected static ?string $pluralModelLabel = 'Contenidos';

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('Contenido')->columnSpanFull()->tabs([

                Tab::make('Principal')->icon('heroicon-o-document')->schema([
                    Grid::make(2)->schema([
                        TextInput::make('title')->required()->maxLength(511)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null
                            )->label('Título'),
                        TextInput::make('slug')->required()->maxLength(255)
                            ->unique(ignoreRecord: true)->rule('alpha_dash')->label('Slug'),
                        // Un Editor crea sólo en sus plataformas, y la cambia
                        // (como el autor) sólo si el contenido es suyo (F5).
                        Select::make('platform_id')->required()
                            ->options(fn (?Content $record): array => self::platformOptions($record))
                            ->in(fn (?Content $record): array => array_keys(self::platformOptions($record)))
                            ->default(fn () => request()->query('platform_id'))
                            ->disabled(fn (?Content $record): bool => $record !== null && Gate::denies('manage', $record))
                            ->searchable()->label('Plataforma'),
                        Select::make('author_id')->required()
                            ->getSearchResultsUsing(fn (string $search): array => self::authorResults($search))
                            ->getOptionLabelUsing(fn ($value): ?string => User::query()->find($value)?->name)
                            ->rule(fn (?Content $record): Closure => self::authorRule($record))
                            ->default(fn () => auth()->id())
                            // Un Editor que crea es el autor; al editar, sólo
                            // quien tiene el control (administración o autoría)
                            // cambia el autor.
                            ->disabled(fn (?Content $record): bool => $record === null ? ! self::isAdmin() : Gate::denies('manage', $record))
                            ->searchable()
                            ->searchPrompt('Escribe al menos dos letras del nombre')
                            ->label('Autor'),
                        Select::make('status_id')
                            ->options(fn (?Content $record): array => self::statusOptions($record))
                            ->in(fn (?Content $record): array => array_keys(self::statusOptions($record)))
                            ->required()->label('Estado')
                            ->default(ContentStatusEnum::Draft->value)
                            ->live()
                            // Publicado es definitivo: sólo se oculta con
                            // «Activo» o se elimina. El modelo lo rechaza
                            // también si llega por otro lado.
                            ->disabled(fn (?Content $record): bool => $record?->isPublished() ?? false)
                            ->helperText(fn (?Content $record): string => $record?->isPublished()
                                ? 'Un contenido publicado no cambia de estado. Para retirarlo de las webs, desmarca «Activo» en Visibilidad; para quitarlo del todo, elimínalo.'
                                : 'Al publicar se pone la fecha de publicación y se marca «Activo».'),
                        Select::make('type_id')
                            ->relationship('type', 'name')->required()->label('Tipo'),
                        DateTimePicker::make('scheduled_at')->label('Publicar el')
                            ->visible(fn (Get $get): bool => (int) $get('status_id') === ContentStatusEnum::Scheduled->value)
                            ->required(fn (Get $get): bool => (int) $get('status_id') === ContentStatusEnum::Scheduled->value)
                            ->after('now')
                            ->rule(fn (?Content $record): ?Closure => self::scheduleRule($record))
                            ->seconds(false)
                            ->validationMessages(['after' => 'La fecha de publicación tiene que ser futura.'])
                            ->helperText(fn (?Content $record): string => $record !== null && Gate::denies('publish', $record)
                                ? 'Como colaborador, con al menos '.ContentPolicy::CONTRIBUTOR_SCHEDULE_DAYS.' días de margen: así un administrador lo revisa antes. Se publica sola y queda activa.'
                                : 'Se publica sola, como mucho 5 minutos después de esta hora, y queda activa.'),
                    ]),
                    Textarea::make('excerpt')->maxLength(1023)->rows(2)
                        ->columnSpanFull()->label('Extracto'),
                    ImageCropperUpload::makeImage('image_id')
                        ->asFileRecord()
                        ->storeFiles(false)
                        ->cover16x9()
                        ->directory('contents')
                        ->columnSpanFull()->label('Imagen principal'),
                ]),

                Tab::make('Visibilidad')->icon('heroicon-o-eye')->schema([
                    Grid::make(3)->schema([
                        Toggle::make('is_active')->label('Activo')
                            ->helperText('A las webs sólo va lo publicado y activo.'),
                        Toggle::make('is_featured')->label('Destacado'),
                        Toggle::make('is_copyright_valid')->label('Copyright OK'),
                        Toggle::make('is_comment_enabled')->label('Permitir comentarios'),
                        Toggle::make('is_comment_anonymous')->label('Coment. anónimos'),
                        Toggle::make('is_visible_on_home')->label('En home'),
                        Toggle::make('is_visible_on_menu')->label('En menú'),
                        Toggle::make('is_visible_on_footer')->label('En footer'),
                        Toggle::make('is_visible_on_sidebar')->label('En sidebar'),
                        Toggle::make('is_visible_on_search')->label('En búsqueda')->default(true),
                        Toggle::make('is_visible_on_archive')->label('En archivo'),
                        Toggle::make('is_visible_on_rss')->label('En RSS'),
                        Toggle::make('is_visible_on_sitemap')->label('Sitemap'),
                        Toggle::make('is_visible_on_sitemap_news')->label('Sitemap noticias'),
                    ]),
                    Grid::make(2)->schema([
                        DateTimePicker::make('published_at')->label('Publicado en')
                            ->disabled()
                            ->helperText('Se pone sola al publicar: a mano, con la acción «Publicar» o al llegar la fecha programada.'),
                    ]),
                ]),

                Tab::make('Taxonomías')->icon('heroicon-o-tag')->schema([
                    Select::make('technologies')->multiple()->relationship('technologies', 'name')
                        ->preload()->searchable()->label('Tecnologías'),
                    // Nota: tags/categories del módulo Content están filtradas por platform,
                    // gestionarlas exige seleccionar primero la plataforma. Se exponen a
                    // través de los RelationManagers en una iteración posterior.
                ]),

                Tab::make('Vídeo y enlaces')->icon('heroicon-o-film')->schema([
                    Group::make()
                        ->relationship('metadata')
                        ->schema([
                            YoutubeVideoField::make('youtube_video_id')
                                ->label('Vídeo de YouTube')
                                ->helperText('Busca un vídeo en el canal de la plataforma seleccionada y selecciónalo. La URL se genera automáticamente al guardar.')
                                ->channels(fn () => Platform::query()
                                    ->whereNotNull('youtube_channel_id')
                                    ->pluck('youtube_channel_id', 'id')
                                    ->toArray())
                                ->platformNames(fn () => Platform::query()
                                    ->whereNotNull('youtube_channel_id')
                                    ->pluck('title', 'id')
                                    ->toArray())
                                ->columnSpanFull(),
                            Grid::make(2)->schema([
                                TextInput::make('web')->url()->maxLength(255)->label('Web'),
                                TextInput::make('youtube_channel')->maxLength(255)->label('Canal de YouTube'),
                                TextInput::make('github')->maxLength(255)->label('GitHub'),
                                TextInput::make('gitlab')->maxLength(255)->label('GitLab'),
                                TextInput::make('telegram_channel')->maxLength(255)->label('Canal de Telegram'),
                                TextInput::make('mastodon')->maxLength(255)->label('Mastodon'),
                                TextInput::make('twitter')->maxLength(255)->label('Twitter / X'),
                            ]),
                        ]),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable()->limit(60)->label('Título'),
                TextColumn::make('platform.title')->badge()->label('Plataforma')->toggleable(),
                TextColumn::make('type.name')->badge()->label('Tipo')->toggleable(),
                TextColumn::make('status_id')->badge()->label('Estado')
                    // Los contenidos que vienen de la v1 no tienen estado.
                    ->formatStateUsing(fn ($state): string => ContentStatusEnum::tryFrom((int) $state)?->label() ?? 'Sin estado')
                    ->placeholder('Sin estado')
                    ->color(fn ($state): string => match (ContentStatusEnum::tryFrom((int) $state)) {
                        ContentStatusEnum::Published => 'success',
                        ContentStatusEnum::Scheduled => 'info',
                        ContentStatusEnum::Draft => 'warning',
                        ContentStatusEnum::ToRemove, ContentStatusEnum::CopyrightProtected => 'danger',
                        default => 'gray',
                    }),
                IconColumn::make('is_active')->boolean()->label('Activo'),
                IconColumn::make('is_featured')->boolean()->label('Dest.')->toggleable(),
                TextColumn::make('published_at')->label('Publicado en')->dateTime('d/m/Y')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('platform_id')->relationship('platform', 'title')->label('Plataforma'),
                SelectFilter::make('status_id')->options(self::statusOptions())->label('Estado'),
                SelectFilter::make('type_id')->relationship('type', 'name')->label('Tipo'),
                TernaryFilter::make('is_active')->label('Activo'),
                TernaryFilter::make('is_featured')->label('Destacado'),
            ])
            ->recordActions([
                Action::make('preview')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Content $record) => url("/content/{$record->platform?->slug}/{$record->slug}"), true)
                    ->label('Ver'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                // Registro a registro: lo que no se puede borrar o publicar se
                // salta y Filament dice cuántos (F5).
                BulkActionGroup::make([
                    DeleteBulkAction::make()->authorizeIndividualRecords('delete'),
                    // Las mismas reglas que el formulario: estado «publicado»,
                    // fecha de publicación si no tenía y «Activo» marcado.
                    BulkAction::make('publish')
                        ->authorizeIndividualRecords('publish')
                        ->icon('heroicon-o-check')->requiresConfirmation()
                        ->modalDescription('Se publican ahora y quedan visibles en las webs. Un contenido publicado ya no vuelve a borrador ni a programado.')
                        // Sin devolver nada: Livewire mandaría al navegador lo
                        // que devuelva la acción, y `each()` devuelve los modelos.
                        ->action(function (Enumerable $records): void {
                            $records->each(fn (Content $content) => $content->publish());
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle('Contenidos publicados')
                        ->label('Publicar'),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    /**
     * Roles que pueden ser autores de un contenido.
     */
    private const AUTHOR_ROLES = [UserRoleEnum::SuperAdmin->value, UserRoleEnum::Admin->value, UserRoleEnum::Editor->value];

    /**
     * Un Editor ve sólo los contenidos donde es autor o colaborador (B3). Las
     * plataformas asignadas dicen dónde crea, no qué ve.
     */
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = self::currentUser();

        if ($user !== null && ! $user->isAdmin()) {
            $query->where(fn (Builder $query) => $query
                ->where('author_id', $user->id)
                ->orWhereHas('contributors', fn (Builder $contributors) => $contributors->whereKey($user->id)));
        }

        return $query;
    }

    /**
     * Estados con su etiqueta en español, desde el enum y no desde la tabla
     * (allí hay nombres en inglés, como «Copyright Protected»). Quien no puede
     * publicar al momento (un colaborador) no tiene «Publicado».
     *
     * @return array<int, string>
     */
    private static function statusOptions(?Content $record = null): array
    {
        $canPublish = $record === null || Gate::allows('publish', $record) || $record->isPublished();

        return collect(ContentStatusEnum::cases())
            ->reject(fn (ContentStatusEnum $status): bool => $status === ContentStatusEnum::Published && ! $canPublish)
            ->mapWithKeys(fn (ContentStatusEnum $status): array => [$status->value => $status->label()])
            ->all();
    }

    /**
     * Plataformas donde se puede crear o a las que se puede mover el
     * contenido: todas para administración; las asignadas para un Editor, más
     * la que ya tiene el contenido.
     *
     * @return array<int, string>
     */
    private static function platformOptions(?Content $record): array
    {
        $user = self::currentUser();
        $query = Platform::query()->orderBy('title');

        if ($user === null || ! $user->isAdmin()) {
            $query->whereIn('id', $user?->platforms()->pluck('platforms.id') ?? []);
        }

        $options = $query->pluck('title', 'id')->all();

        if ($record?->platform !== null) {
            $options[$record->platform_id] ??= (string) $record->platform->title;
        }

        return $options;
    }

    /**
     * Posibles autores: buscando por nombre desde dos letras, 50 como mucho y
     * sin email (B4).
     *
     * @return array<int, string>
     */
    private static function authorResults(string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        return User::query()
            ->whereIn('role_id', self::AUTHOR_ROLES)
            ->where('is_active', true)
            ->where('name', 'ilike', '%'.trim($search).'%')
            ->orderBy('name')
            ->limit(50)
            ->pluck('name', 'id')
            ->all();
    }

    /**
     * Un autor nuevo tiene que ser una cuenta de administración o un Editor.
     * El que ya tiene el contenido vale aunque no lo sea: si no, no se podría
     * guardar nada de un contenido con un autor antiguo de otro rol.
     */
    private static function authorRule(?Content $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            if ($record !== null && (int) $value === (int) $record->author_id) {
                return;
            }

            $eligible = User::query()->whereKey($value)->whereIn('role_id', self::AUTHOR_ROLES)->exists();

            if (! $eligible) {
                $fail('El autor tiene que ser un Editor o una cuenta de administración.');
            }
        };
    }

    /**
     * Un colaborador programa con al menos una semana de margen (DUDA-2).
     */
    private static function scheduleRule(?Content $record): ?Closure
    {
        if ($record === null) {
            return null;
        }

        return function (string $attribute, mixed $value, Closure $fail) use ($record): void {
            if (filled($value) && Gate::denies('schedule', [$record, Carbon::parse((string) $value)])) {
                $fail('Como colaborador, programa con al menos '.ContentPolicy::CONTRIBUTOR_SCHEDULE_DAYS.' días de margen: así un administrador lo revisa antes de publicarse.');
            }
        };
    }

    private static function currentUser(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private static function isAdmin(): bool
    {
        return self::currentUser()?->isAdmin() ?? false;
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PagesRelationManager::class,
            RelationManagers\GalleriesRelationManager::class,
            RelationManagers\ContributorsRelationManager::class,
            RelationManagers\RelatedRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContents::route('/'),
            'create' => CreateContent::route('/create'),
            'edit' => EditContent::route('/{record}/edit'),
        ];
    }
}
