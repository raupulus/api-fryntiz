<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recalcula en base de datos la duración en segundos de las rachas de teclado
 * cuya columna `duration` esté a NULL, sin cargar la tabla completa en RAM.
 */
class KeyCounterGenerateDuration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'keycounter:generate_duration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenera la duración para los registros que no se le calculara automáticamente';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $updated = DB::table('keycounter_keyboard')
            ->whereNull('duration')
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->update([
                'duration' => DB::raw('GREATEST(0, ROUND(EXTRACT(EPOCH FROM (end_at - start_at))))::bigint'),
            ]);

        $this->info("Duración recalculada en {$updated} registro(s).");

        return self::SUCCESS;
    }
}
