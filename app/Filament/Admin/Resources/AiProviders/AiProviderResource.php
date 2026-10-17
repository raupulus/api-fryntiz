<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AiProviders;

use App\Enums\AiProviderEnum;
use App\Filament\Admin\Resources\AiProviders\Pages\CreateAiProvider;
use App\Filament\Admin\Resources\AiProviders\Pages\EditAiProvider;
use App\Filament\Admin\Resources\AiProviders\Pages\ListAiProviders;
use App\Models\AiProvider;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

/**
 * Los proveedores de IA del asistente del editor de páginas, con su modelo y
 * su clave. Sólo para el superadmin (`AiProviderPolicy`): valen para todas
 * las plataformas.
 *
 * La clave se guarda cifrada. Al editar llega al navegador del superadmin para
 * poder verla con el ojo; en la tabla no sale nunca.
 */
class AiProviderResource extends Resource
{
    protected static ?string $model = AiProvider::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'proveedor de IA';

    protected static ?string $pluralModelLabel = 'Proveedores de IA';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Proveedor')->columns(2)->columnSpanFull()->schema([
                Select::make('provider')
                    ->label('Proveedor')
                    ->options(fn (?AiProvider $record): array => self::providerOptions($record))
                    ->required()
                    ->in(fn (?AiProvider $record): array => array_keys(self::providerOptions($record)))
                    ->disabledOn('edit')
                    ->live()
                    ->afterStateUpdated(function (?string $state, Set $set): void {
                        $provider = AiProviderEnum::tryFrom((string) $state);
                        $set('requests_reasoning_summary', $provider?->summarizesReasoningByDefault() ?? false);
                    }),
                TextInput::make('model')
                    ->label('Modelo')
                    ->required()
                    ->maxLength(255)
                    ->placeholder(fn (Get $get): ?string => AiProviderEnum::tryFrom((string) $get('provider'))?->modelExample())
                    ->helperText(fn (Get $get): HtmlString|string => self::modelHelp(AiProviderEnum::tryFrom((string) $get('provider')))),
                TextInput::make('api_key')
                    ->label('Clave de la API')
                    ->password()
                    ->revealable()
                    ->autocomplete('off')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(1024)
                    // Vacía al editar: se queda la que había.
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->helperText(fn (?AiProvider $record): string => match ($record?->keyStatus()) {
                        AiProvider::KEY_UNREADABLE => 'La clave guardada no se puede leer: se cifró con otra APP_KEY. Vuelve a pegarla.',
                        AiProvider::KEY_SET => 'Se guarda cifrada. Déjala como está para no cambiarla.',
                        default => 'Se guarda cifrada.',
                    })
                    ->columnSpanFull(),
            ]),

            Section::make('Uso')->columns(2)->columnSpanFull()->schema([
                Toggle::make('is_active')
                    ->label('Activo')
                    ->default(true)
                    ->helperText('Sale en el selector del asistente.'),
                Toggle::make('is_default')
                    ->label('Predeterminado')
                    ->helperText('El que sale elegido en el asistente. Sólo puede haber uno.'),
                Toggle::make('sends_reasoning_level')
                    ->label('Elegir el razonamiento en el asistente')
                    ->default(true)
                    ->helperText(fn (Get $get): string => AiProviderEnum::tryFrom((string) $get('provider')) === AiProviderEnum::Mistral
                        ? 'En cada petición se elige si razona o no (Mistral no tiene niveles). Desmárcalo si el modelo no razona: le da error.'
                        : 'En cada petición se elige cuánto razona (bajo, medio, alto). Desmárcalo si el modelo no razona: le da error.'),
                Toggle::make('requests_reasoning_summary')
                    ->label('Pedir el resumen del razonamiento')
                    ->default(fn (Get $get): bool => AiProviderEnum::tryFrom((string) $get('provider'))?->summarizesReasoningByDefault() ?? false)
                    ->visible(fn (Get $get): bool => AiProviderEnum::tryFrom((string) $get('provider'))?->canSummarizeReasoning() ?? true)
                    ->helperText('Se ve en el asistente mientras piensa. Si «Probar» falla con esto marcado, el modelo no lo admite.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provider')->label('Proveedor')
                    ->formatStateUsing(fn (AiProviderEnum $state): string => $state->fullLabel()),
                TextColumn::make('model')->label('Modelo')->searchable(),
                TextColumn::make('key_status')->label('Clave')->badge()
                    ->state(fn (AiProvider $record): string => $record->keyStatus())
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AiProvider::KEY_SET => 'Puesta',
                        AiProvider::KEY_UNREADABLE => 'Ilegible',
                        default => 'Falta',
                    })
                    ->color(fn (string $state): string => $state === AiProvider::KEY_SET ? 'success' : 'danger'),
                IconColumn::make('is_active')->label('Activo')->boolean(),
                IconColumn::make('is_default')->label('Predeterminado')->boolean(),
                TextColumn::make('updated_at')->label('Actualizado')->dateTime('d/m/Y H:i')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                self::probeAction(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id');
    }

    /**
     * Una llamada real mínima con la clave y el modelo guardados.
     */
    public static function probeAction(): Action
    {
        return Action::make('probe')
            ->label('Probar')
            ->icon('heroicon-o-signal')
            ->color('gray')
            ->action(function (AiProvider $record): void {
                $result = $record->connector()->probe($record);

                $notification = Notification::make()->title($result['ok'] ? "{$record->label()} responde" : "{$record->label()} no responde");

                ($result['ok']
                    ? $notification->success()->body('Respuesta: '.$result['message'])
                    : $notification->danger()->body($result['message'])->persistent()
                )->send();
            });
    }

    /**
     * Los proveedores que faltan por dar de alta, más el del registro.
     *
     * @return array<string, string>
     */
    private static function providerOptions(?AiProvider $record): array
    {
        $taken = AiProvider::query()
            ->when($record !== null, fn ($query) => $query->whereKeyNot($record->getKey()))
            ->pluck('provider')
            ->map(fn (AiProviderEnum $provider): string => $provider->value)
            ->all();

        return array_diff_key(AiProviderEnum::options(), array_flip($taken));
    }

    private static function modelHelp(?AiProviderEnum $provider): HtmlString|string
    {
        if ($provider === null) {
            return 'El identificador exacto del modelo en la API. «Probar» comprueba que responde.';
        }

        return new HtmlString(sprintf(
            'El identificador exacto del modelo en la API (<a href="%s" target="_blank" rel="noopener" style="text-decoration: underline">modelos de %s</a>). «Probar» comprueba que responde.',
            e($provider->modelsUrl()),
            e($provider->label()),
        ));
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAiProviders::route('/'),
            'create' => CreateAiProvider::route('/create'),
            'edit' => EditAiProvider::route('/{record}/edit'),
        ];
    }
}
