<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\CV\Curriculum\Pages;

use App\Filament\Admin\Resources\CV\Curriculum\CurriculumResource;
use App\Filament\Concerns\HasImageFileUpload;
use App\Models\CV\Curriculum;
use App\Services\Cv\CurriculumPdfService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class EditCurriculum extends EditRecord
{
    use HasImageFileUpload;

    protected static string $resource = CurriculumResource::class;

    /**
     * El currículum de la página, ya tipado.
     *
     * `getRecord()` de Filament devuelve `Model` a secas, así que el análisis
     * estático no ve ni `pdf_path` ni que sirve para `CurriculumPdfService`.
     * Se estrecha aquí una vez en lugar de repetir un `@var` en cada uso.
     */
    protected function curriculum(): Curriculum
    {
        /** @var Curriculum $cv */
        $cv = $this->getRecord();

        return $cv;
    }

    protected function getHeaderActions(): array
    {
        return [
            // B5: el PDF es un fichero generado y guardado, no se rehace en cada
            // visita. Este botón lo regenera ahora; si se olvida, el comando
            // nocturno `cv:regenerate-pdfs` recoge lo que quedó marcado.
            Action::make('regenerar_pdf')
                ->label('Regenerar PDF')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('¿Regenerar el PDF?')
                ->modalDescription('Se genera de nuevo desde los datos actuales y reemplaza al anterior.')
                ->action(function (CurriculumPdfService $service) {
                    try {
                        $service->generate($this->curriculum());

                        Notification::make()
                            ->title('PDF regenerado')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('No se ha podido generar el PDF')
                            ->body($e->getMessage())
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            Action::make('descargar_pdf')
                ->label('Descargar PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->color('gray')
                ->visible(fn (): bool => filled($this->curriculum()->pdf_path))
                ->url(fn (): string => Storage::disk('public')->url($this->curriculum()->pdf_path))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['image_id']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->resolveImageUpload($data, 'image_id', 'curriculum');
    }
}
