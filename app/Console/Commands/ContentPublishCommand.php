<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\PublishContentAction;
use Illuminate\Console\Command;

class ContentPublishCommand extends Command
{
    protected $signature = 'content:publish';

    protected $description = 'Publicar contenidos programados cuya fecha ya ha pasado';

    public function handle(PublishContentAction $action): int
    {
        $count = $action->execute();
        $this->info("Se han publicado {$count} contenido(s) programado(s).");

        return self::SUCCESS;
    }
}
