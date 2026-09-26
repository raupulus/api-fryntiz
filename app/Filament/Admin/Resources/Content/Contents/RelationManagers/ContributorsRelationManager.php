<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Content\Contents\RelationManagers;

use App\Enums\UserRoleEnum;
use App\Models\Content\Content;
use App\Models\User;
use App\Services\Content\ContentContributorService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use LogicException;

/**
 * Colaboradores de un contenido (F5 del plan de contenidos del 2026-09-24).
 *
 * - Los ve quien ve el contenido; los cambia la administración o el autor
 *   (política `manage`).
 * - Añadir busca Editores activos por nombre, desde dos letras y sin precargar
 *   la lista; un Editor no ve emails (B4).
 * - Quitar deja la fila borrada: es una baja manual, que el colaborador
 *   automático de la plataforma respeta (`ContentContributorService`).
 */
class ContributorsRelationManager extends RelationManager
{
    protected static string $relationship = 'contributors';

    protected static ?string $title = 'Colaboradores';

    /**
     * Por el contenido, no por `viewAny` de usuarios, que un Editor no tiene.
     */
    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return Gate::allows('view', $ownerRecord);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            // Relación inversa explícita: Filament adivinaría "contents"
            // (plural del modelo padre Content), que no existe en User.
            ->inverseRelationship('contributedContents')
            ->columns([
                TextColumn::make('name')->label('Nombre'),
                TextColumn::make('email')->label('Email')->visible(fn (): bool => $this->isAdmin()),
            ])
            ->headerActions([
                Action::make('addContributor')
                    ->label('Añadir colaborador')
                    ->icon('heroicon-o-user-plus')
                    ->authorize(fn (): bool => Gate::allows('manage', $this->ownerContent()))
                    ->schema([
                        Select::make('user_id')
                            ->label('Editor')
                            ->required()
                            ->searchable()
                            ->searchPrompt('Escribe al menos dos letras del nombre')
                            ->getSearchResultsUsing(fn (string $search): array => $this->candidates($search))
                            ->getOptionLabelUsing(fn ($value): ?string => User::query()->find($value)?->name),
                    ])
                    ->action(function (array $data): void {
                        $user = User::query()->find($data['user_id']);

                        if (! $user instanceof User || ! $user->isEditor() || ! $user->is_active) {
                            Notification::make()->danger()->title('Sólo un Editor activo puede ser colaborador')->send();

                            return;
                        }

                        app(ContentContributorService::class)->add($this->ownerContent(), $user);
                        Notification::make()->success()->title("{$user->name} ya colabora en este contenido")->send();
                    }),
            ])
            ->recordActions([
                Action::make('removeContributor')
                    ->label('Quitar')
                    ->icon('heroicon-o-user-minus')
                    ->color('danger')
                    ->authorize(fn (): bool => Gate::allows('manage', $this->ownerContent()))
                    ->requiresConfirmation()
                    ->modalHeading(fn (User $record): string => "Quitar a {$record->name}")
                    ->modalDescription('Deja de poder editar este contenido. Si tiene el colaborador automático en esta plataforma, no vuelve a entrar aquí; se le puede volver a añadir a mano.')
                    ->action(fn (User $record) => app(ContentContributorService::class)->remove($this->ownerContent(), $record)),
            ]);
    }

    /**
     * Editores activos por nombre, que aún no colaboran y no son el autor.
     *
     * @return array<int, string>
     */
    private function candidates(string $search): array
    {
        if (mb_strlen(trim($search)) < 2) {
            return [];
        }

        $content = $this->ownerContent();

        return User::query()
            ->where('role_id', UserRoleEnum::Editor->value)
            ->where('is_active', true)
            ->where('name', 'ilike', '%'.trim($search).'%')
            ->whereKeyNot(array_filter([$content->author_id]))
            ->whereNotIn('id', $content->contributors()->pluck('users.id'))
            ->orderBy('name')
            ->limit(50)
            ->pluck('name', 'id')
            ->all();
    }

    private function ownerContent(): Content
    {
        $owner = $this->getOwnerRecord();

        if (! $owner instanceof Content) {
            throw new LogicException('Los colaboradores cuelgan de un contenido.');
        }

        return $owner;
    }

    private function isAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isAdmin();
    }
}
