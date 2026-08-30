<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokens\Pages;

use App\Filament\Admin\Resources\ApiTokens\ApiTokenResource;
use App\Models\Hardware\HardwareDevice;
use App\Services\Hardware\DeviceTokenService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateApiToken extends CreateRecord
{
    protected static string $resource = ApiTokenResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // No creamos via Eloquent normal — usamos Sanctum createToken
        $this->plainTextToken = null;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Se emite por `DeviceTokenService`, que es la única fuente de emisión
        // de tokens de dispositivo: valida que las abilities están en el
        // catálogo, comprueba el propietario y añade "device:{id}". Antes esta
        // página llamaba a `createToken()` a pelo con "*" por defecto.
        $device = HardwareDevice::query()->findOrFail($data['device_id']);

        if ((int) $device->user_id !== (int) $data['tokenable_id']) {
            throw ValidationException::withMessages([
                'device_id' => 'El dispositivo elegido no pertenece al usuario seleccionado.',
            ]);
        }

        $expiresAt = ! empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

        $token = app(DeviceTokenService::class)->issue($device, $data['abilities'] ?? [], $expiresAt);

        // El nombre que escribió el administrador manda sobre el que pone el
        // servicio, para poder distinguir dos tokens del mismo cacharro.
        if (! empty($data['name'])) {
            $token->accessToken->forceFill(['name' => $data['name']])->save();
        }

        $this->plainTextToken = $token->plainTextToken;

        return $token->accessToken;
    }

    protected ?string $plainTextToken = null;

    protected function getCreatedNotification(): ?Notification
    {
        if ($this->plainTextToken) {
            return Notification::make()
                ->title('Token creado — cópialo ahora')
                ->body($this->plainTextToken)
                ->persistent()
                ->success();
        }

        return Notification::make()
            ->title('Token creado')
            ->success();
    }
}
