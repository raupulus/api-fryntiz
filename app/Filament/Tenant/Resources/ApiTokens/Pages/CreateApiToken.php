<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\ApiTokens\Pages;

use App\Filament\Tenant\Resources\ApiTokens\ApiTokenResource;
use App\Models\Hardware\HardwareDevice;
use App\Services\Hardware\DeviceTokenService;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Emisión de un token de dispositivo desde el panel del usuario.
 *
 * Va por `DeviceTokenService`, que es la fuente única: comprueba que las
 * abilities están en el catálogo, que el dispositivo es del usuario, y añade
 * `device:{id}`. Sin eso, un usuario emitiendo su propio token podría acabar
 * con uno que valga para cualquier cosa.
 */
class CreateApiToken extends CreateRecord
{
    protected static string $resource = ApiTokenResource::class;

    protected ?string $plainTextToken = null;

    protected function handleRecordCreation(array $data): Model
    {
        $device = HardwareDevice::query()->findOrFail($data['device_id']);

        // El dispositivo tiene que ser suyo. El `Select` ya sólo ofrece los
        // suyos, pero el formulario llega por HTTP y eso se puede falsear.
        if ((int) $device->user_id !== (int) auth()->id()) {
            throw ValidationException::withMessages([
                'device_id' => 'Ese dispositivo no es tuyo.',
            ]);
        }

        $expiresAt = ! empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

        $token = app(DeviceTokenService::class)->issue($device, $data['abilities'] ?? [], $expiresAt);

        if (! empty($data['name'])) {
            $token->accessToken->forceFill(['name' => $data['name']])->save();
        }

        $this->plainTextToken = $token->plainTextToken;

        return $token->accessToken;
    }

    protected function getCreatedNotification(): ?Notification
    {
        if ($this->plainTextToken === null) {
            return Notification::make()->title('Token creado')->success();
        }

        return Notification::make()
            ->title('Token creado — cópialo ahora')
            ->body($this->plainTextToken)
            ->persistent()
            ->success();
    }
}
