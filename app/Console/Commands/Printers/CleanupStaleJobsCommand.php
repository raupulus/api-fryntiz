<?php

declare(strict_types=1);

namespace App\Console\Commands\Printers;

use App\Enums\PrinterStatusEnum;
use App\Enums\PrintJobStatusEnum;
use App\Events\Printers\PrintJobStatusUpdated;
use App\Models\Printer;
use App\Models\PrinterStack;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Recupera o finaliza trabajos de impresión que han quedado bloqueados en estado 'processing'.
 */
class CleanupStaleJobsCommand extends Command
{
    protected $signature = 'printers:cleanup-stale-jobs
                            {--minutes=15 : Minutos transcurridos en estado processing para considerar un trabajo bloqueado}';

    protected $description = 'Recupera o finaliza trabajos de impresión bloqueados y marca impresoras inactivas como offline';

    public function handle(): int
    {
        $minutes = max(1, (int) $this->option('minutes'));
        $threshold = Carbon::now()->subMinutes($minutes);

        // 1. Trabajos bloqueados en estado processing
        $staleJobs = PrinterStack::query()
            ->where('status', PrintJobStatusEnum::Processing)
            ->where('updated_at', '<', $threshold)
            ->with(['printer'])
            ->get();

        $recovered = 0;
        $failed = 0;

        foreach ($staleJobs as $job) {
            if ($job->attempts < 3) {
                $job->status = PrintJobStatusEnum::Pending;
                $job->error_message = "Reencolado automáticamente tras superar {$minutes} minutos bloqueado (intento {$job->attempts}/3).";
                $job->save();
                $recovered++;

                Log::info('Stale print job recovered to pending', [
                    'job_id' => $job->id,
                    'printer_id' => $job->printer_id,
                    'attempts' => $job->attempts,
                ]);
            } else {
                $job->status = PrintJobStatusEnum::Failed;
                $job->error_message = "Tiempo de espera agotado tras {$job->attempts} intentos fallidos.";
                $job->save();
                $failed++;

                Log::warning('Stale print job marked as failed after max attempts', [
                    'job_id' => $job->id,
                    'printer_id' => $job->printer_id,
                    'attempts' => $job->attempts,
                ]);
            }

            broadcast(new PrintJobStatusUpdated($job));
        }

        // 2. Marcar como offline impresoras que lleven más de 10 minutos sin dar señales
        $offlinePrinters = Printer::query()
            ->where('status', '!=', PrinterStatusEnum::Offline)
            ->where('last_seen_at', '<', Carbon::now()->subMinutes(10))
            ->update(['status' => PrinterStatusEnum::Offline]);

        $this->info("Proceso completado: {$recovered} trabajos reencolados, {$failed} trabajos marcados como fallidos, {$offlinePrinters} impresoras marcadas como offline.");

        return self::SUCCESS;
    }
}
