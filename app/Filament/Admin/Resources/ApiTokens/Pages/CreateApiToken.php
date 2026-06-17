<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ApiTokens\Pages;

use App\Filament\Admin\Resources\ApiTokens\ApiTokenResource;
use App\Models\User;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

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
        $user = User::findOrFail($data['tokenable_id']);
        $abilities = $data['abilities'] ?? ['*'];
        $expiresAt = ! empty($data['expires_at']) ? Carbon::parse($data['expires_at']) : null;

        $token = $user->createToken($data['name'], $abilities, $expiresAt);
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
