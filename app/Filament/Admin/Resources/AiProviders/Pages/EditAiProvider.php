<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\AiProviders\Pages;

use App\Filament\Admin\Resources\AiProviders\AiProviderResource;
use App\Models\AiProvider;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * @property AiProvider $record
 */
class EditAiProvider extends EditRecord
{
    protected static string $resource = AiProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AiProviderResource::probeAction(),
            DeleteAction::make(),
        ];
    }

    /**
     * La clave está oculta en el modelo (`$hidden`) y no llega sola al
     * formulario: se pone aquí para verla con el ojo. Si no se puede leer,
     * llega vacía y el campo lo avisa.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['api_key'] = $this->record->api_key;

        return $data;
    }
}
