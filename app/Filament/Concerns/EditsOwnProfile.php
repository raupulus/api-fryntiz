<?php

declare(strict_types=1);

namespace App\Filament\Concerns;

use App\Filament\Components\ImageCropperUpload;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Edición del perfil propio, compartida por los dos paneles.
 *
 * Existe para que la página del panel `admin` y la del panel `tenant` sean la
 * misma cosa: dos copias del mismo formulario acaban divergiendo, y en un
 * formulario que toca contraseñas eso es un agujero esperando.
 *
 * Regla de D91: **el email no lo cambia nadie sobre sí mismo.** Se muestra,
 * porque hace falta saber cuál es, pero está deshabilitado y no se escribe al
 * guardar aunque llegue en los datos. Lo cambia un administrador desde la ficha
 * del usuario, y sólo a otros.
 *
 * `$form` no es una propiedad real: la resuelve el `__get()` de
 * `Filament\Schemas\Concerns\ResolvesDynamicLivewireProperties`, que busca el
 * esquema declarado por el método `form()` de la página. Se anota para que el
 * análisis estático no lo tome por un acceso a una propiedad inexistente.
 *
 * @property-read Schema $form
 */
trait EditsOwnProfile
{
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
            'send_email' => $user->settings->send_email ?? true,
            'send_notification' => $user->settings->send_notification ?? true,
            'send_notification_push' => $user->settings->send_notification_push ?? true,
        ]);
    }

    /**
     * El esquema del formulario de perfil.
     *
     * **No se llama `form()` a propósito.** `Filament\Forms\Concerns\InteractsWithForms`
     * también define `form()`, y PHP no resuelve solo una colisión entre dos
     * traits: aborta con un fatal al cargar la clase. Filament 5.6 no lo tenía
     * y 5.7 sí, así que esto reventó al actualizar sin que hubiéramos tocado
     * nada.
     *
     * Cada página declara su `form()` y llama aquí. Es una línea por página, y a
     * cambio el formulario sigue estando en un solo sitio y no depende de un
     * `insteadof` que hay que revisar en cada actualización de Filament.
     */
    public function profileSchema(Schema $schema): Schema
    {
        return $schema->statePath('data')->components([
            Section::make('Datos personales')->columns(2)->schema([
                TextInput::make('name')->required()->maxLength(255)->label('Nombre'),
                TextInput::make('surname')->maxLength(255)->label('Apellidos'),
                TextInput::make('nickname')->maxLength(511)
                    ->unique('users', 'nickname', ignoreRecord: true, modifyRuleUsing: fn ($rule) => $rule->ignore(Auth::id()))
                    ->label('Nickname'),

                // D91: visible pero no editable. Cambiar el propio email es la
                // vía clásica para apropiarse de una cuenta ajena si además se
                // puede pedir un restablecimiento.
                TextInput::make('email')
                    ->email()
                    ->disabled()
                    ->dehydrated(false)
                    ->label('Email')
                    ->helperText('El email sólo lo cambia un administrador.'),

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

        // Sin `email`: aunque llegara en los datos, aquí no se escribe (D91).
        $user->fill([
            'name' => $data['name'],
            'surname' => $data['surname'] ?? null,
            'nickname' => $data['nickname'] ?? null,
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
