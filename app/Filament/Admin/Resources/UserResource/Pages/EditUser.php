<?php

namespace App\Filament\Admin\Resources\UserResource\Pages;

use App\Filament\Admin\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // El email está en $hidden del modelo User, por lo que attributesToArray()
        // lo omite y el campo aparecería vacío. Se inyecta el valor real aquí.
        $data['email'] = $this->record->email;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Campo virtual del toggle: la verificación se gestiona con el Action button.
        unset($data['is_email_verified']);

        return $data;
    }
}
