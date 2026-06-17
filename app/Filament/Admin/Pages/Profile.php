<?php

declare(strict_types=1);

namespace App\Filament\Admin\Pages;

use App\Filament\Components\ImageCropperUpload;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Página "Editar perfil" del panel admin.
 *
 * Accesible desde el user menu — el admin actualiza sus propios datos sin
 * tener que pasar por el UserResource.
 */
class Profile extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?string $title = 'Mi perfil';

    protected static ?string $slug = 'profile';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.admin.pages.profile';

    public ?array $data = [];

    public function mount(): void
    {
        /** @var User $user */
        $user = Auth::user()->load(['details', 'settings']);
        $this->form->fill([
            'name' => $user->name,
            'surname' => $user->surname,
            'nickname' => $user->nickname,
            'email' => $user->email,
            'profile_photo_path' => $user->profile_photo_path,
            'profession' => $user->details?->profession,
            'web' => $user->details?->web,
            'send_email' => $user->settings?->send_email ?? true,
            'send_notification' => $user->settings?->send_notification ?? true,
            'send_notification_push' => $user->settings?->send_notification_push ?? true,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Datos personales')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255)->label('Nombre'),
                TextInput::make('surname')->maxLength(255)->label('Apellidos'),
                TextInput::make('nickname')->maxLength(511)
                    ->unique('users', 'nickname', ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->ignore(Auth::id()))
                    ->label('Nickname'),
                TextInput::make('email')->email()->required()
                    ->unique('users', 'email', ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->ignore(Auth::id()))
                    ->label('Email'),
                ImageCropperUpload::makeImage('profile_photo_path')
                    ->avatar()
                    ->directory('profile-photos')
                    ->columnSpanFull()->label('Foto de perfil'),
            ]),
            Section::make('Detalles')->columns(2)->schema([
                TextInput::make('profession')->maxLength(255)->label('Profesión'),
                TextInput::make('web')->url()->maxLength(255)->label('Web'),
            ]),
            Section::make('Notificaciones')->columns(3)->schema([
                Toggle::make('send_email')->label('Permitir emails'),
                Toggle::make('send_notification')->label('Notificaciones'),
                Toggle::make('send_notification_push')->label('Push'),
            ]),
            Section::make('Cambiar contraseña')->columns(2)->collapsed()->schema([
                TextInput::make('current_password')->password()->revealable()
                    ->dehydrated(false)->label('Contraseña actual'),
                TextInput::make('new_password')->password()->revealable()
                    ->rule(Password::defaults())
                    ->dehydrated(false)->same('new_password_confirmation')
                    ->label('Nueva contraseña'),
                TextInput::make('new_password_confirmation')->password()
                    ->dehydrated(false)->label('Repetir nueva contraseña'),
            ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        /** @var User $user */
        $user = Auth::user();

        if (! empty($data['new_password'])) {
            if (! Hash::check($data['current_password'] ?? '', $user->password)) {
                Notification::make()->title('Contraseña actual incorrecta')->danger()->send();

                return;
            }
            $user->password = Hash::make($data['new_password']);
        }

        $user->fill([
            'name' => $data['name'],
            'surname' => $data['surname'] ?? null,
            'nickname' => $data['nickname'] ?? null,
            'email' => $data['email'],
            'profile_photo_path' => $data['profile_photo_path'] ?? $user->profile_photo_path,
        ])->save();

        $user->details()->updateOrCreate([], [
            'profession' => $data['profession'] ?? null,
            'web' => $data['web'] ?? null,
        ]);

        $user->settings()->updateOrCreate([], [
            'send_email' => (bool) ($data['send_email'] ?? true),
            'send_notification' => (bool) ($data['send_notification'] ?? true),
            'send_notification_push' => (bool) ($data['send_notification_push'] ?? true),
        ]);

        Notification::make()->title('Perfil actualizado')->success()->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Guardar')->submit('save'),
        ];
    }
}
