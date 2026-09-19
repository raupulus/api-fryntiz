<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Galleries\Pages;

use App\Filament\Admin\Resources\Galleries\GalleryResource;
use App\Filament\Concerns\HandlesBatchImageUploads;
use App\Filament\Concerns\HasImageFileUpload;
use App\Models\Gallery;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateGallery extends CreateRecord
{
    use HandlesBatchImageUploads;
    use HasImageFileUpload;

    protected static string $resource = GalleryResource::class;

    /**
     * @var array<int, mixed>
     */
    protected array $initialUploadedImages = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->initialUploadedImages = $data['uploaded_images'] ?? [];
        unset($data['uploaded_images']);

        return $this->resolveImageUpload($data, 'image_id', 'galleries');
    }

    protected function afterCreate(): void
    {
        /** @var Gallery $gallery */
        $gallery = $this->record;

        if (! empty($this->initialUploadedImages)) {
            $result = $this->attachBatchImagesToGallery($this->initialUploadedImages, $gallery, startOrder: 1);

            if ($result['failed'] > 0) {
                Notification::make()
                    ->warning()
                    ->title('Atención al procesar las fotos')
                    ->body("Se han guardado {$result['success']} fotos en la galería, pero {$result['failed']} archivo(s) no pudieron procesarse (comprueba que sean imágenes válidas y no excedan 15 MB).")
                    ->persistent()
                    ->send();
            }
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
