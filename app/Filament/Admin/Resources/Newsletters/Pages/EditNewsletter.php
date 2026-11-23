<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Newsletters\Pages;

use App\Filament\Admin\Resources\Newsletters\NewsletterResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNewsletter extends EditRecord
{
    protected static string $resource = NewsletterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
