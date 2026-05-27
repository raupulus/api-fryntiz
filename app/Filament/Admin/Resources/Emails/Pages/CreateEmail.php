<?php

namespace App\Filament\Admin\Resources\Emails\Pages;

use App\Filament\Admin\Resources\Emails\EmailResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmail extends CreateRecord
{
    protected static string $resource = EmailResource::class;
}
