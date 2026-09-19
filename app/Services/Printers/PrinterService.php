<?php

declare(strict_types=1);

namespace App\Services\Printers;

use App\Enums\PrinterStatusEnum;
use App\Enums\PrintJobFormatEnum;
use App\Enums\PrintJobStatusEnum;
use App\Events\Printers\PrintJobCreated;
use App\Events\Printers\PrintJobStatusUpdated;
use App\Models\Printer;
use App\Models\PrinterStack;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Servicio encargado de la lógica de negocio para periféricos de impresión y colas de trabajo.
 */
class PrinterService
{
    /**
     * Obtiene el listado de impresoras paginadas, opcionalmente filtradas por propietario o atributos.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, mixed>
     */
    public function getPrinters(?int $userId = null, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Printer::query()
            ->with(['hardwareDevice'])
            ->withCount([
                'printStack as pending_jobs_count' => fn (Builder $q) => $q->where('status', PrintJobStatusEnum::Pending),
            ]);

        if ($userId !== null) {
            $query->whereHas('hardwareDevice', fn (Builder $q) => $q->where('user_id', $userId));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['printer_type'])) {
            $query->where('printer_type', $filters['printer_type']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    /**
     * Obtiene una impresora por ID, verificando opcionalmente el propietario.
     */
    public function getPrinter(int $printerId, ?int $userId = null): ?Printer
    {
        $query = Printer::query()
            ->with(['hardwareDevice'])
            ->withCount([
                'printStack as pending_jobs_count' => fn (Builder $q) => $q->where('status', PrintJobStatusEnum::Pending),
            ]);

        if ($userId !== null) {
            $query->whereHas('hardwareDevice', fn (Builder $q) => $q->where('user_id', $userId));
        }

        return $query->find($printerId);
    }

    /**
     * Obtiene el historial o cola de trabajos de una impresora.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, mixed>
     */
    public function getJobsForPrinter(Printer $printer, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = PrinterStack::query()
            ->where('printer_id', $printer->id)
            ->with(['user']);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['format'])) {
            $query->where('format', $filters['format']);
        }

        if (isset($filters['is_favorite'])) {
            $query->where('is_favorite', filter_var($filters['is_favorite'], FILTER_VALIDATE_BOOLEAN));
        }

        return $query->orderByDesc('priority')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Obtiene las plantillas favoritas de una impresora.
     *
     * @return LengthAwarePaginator<int, mixed>
     */
    public function getFavoritesForPrinter(Printer $printer, int $perPage = 20): LengthAwarePaginator
    {
        return PrinterStack::query()
            ->where('printer_id', $printer->id)
            ->with(['user'])
            ->favorites()
            ->orderByDesc('updated_at')
            ->paginate($perPage);
    }

    /**
     * Obtiene un trabajo específico por ID.
     */
    public function getJob(int $jobId): ?PrinterStack
    {
        return PrinterStack::with(['printer.hardwareDevice', 'user'])->find($jobId);
    }

    /**
     * Encola un nuevo trabajo de impresión para una impresora física.
     *
     * Valida el formato y el tamaño máximo de payload (KB) antes de persistir.
     * Emite el evento de broadcast `PrintJobCreated` en el canal privado de la impresora.
     *
     * @param  array{
     *     content: string,
     *     format?: string|PrintJobFormatEnum|null,
     *     note?: string|null,
     *     priority?: int|null,
     *     is_favorite?: bool|null
     * }  $data
     */
    public function enqueueJob(Printer $printer, array $data, ?User $user = null): PrinterStack
    {
        if (! $printer->is_active) {
            throw new RuntimeException('La impresora seleccionada no está activa para recibir nuevos trabajos.');
        }

        $format = ! empty($data['format'])
            ? ($data['format'] instanceof PrintJobFormatEnum ? $data['format'] : PrintJobFormatEnum::tryFrom((string) $data['format']))
            : $printer->default_format;

        if ($format === null) {
            throw new InvalidArgumentException('El formato especificado no es válido.');
        }

        if (! $printer->supportsFormat($format)) {
            throw new InvalidArgumentException(
                "El formato '{$format->value}' no está soportado por esta impresora. Formatos permitidos: ".implode(', ', (array) $printer->supported_formats)
            );
        }

        $content = $data['content'];
        $maxBytes = $printer->max_payload_kb * 1024;
        if (strlen($content) > $maxBytes) {
            throw new InvalidArgumentException(
                "El contenido excede el tamaño máximo permitido para esta impresora ({$printer->max_payload_kb} KB)."
            );
        }

        $job = DB::transaction(function () use ($printer, $data, $format, $user, $content) {
            return PrinterStack::create([
                'printer_id' => $printer->id,
                'user_id' => $user?->id,
                'content' => $content,
                'format' => $format,
                'note' => $data['note'] ?? null,
                'priority' => $data['priority'] ?? 0,
                'attempts' => 0,
                'print_count' => 0,
                'is_favorite' => $data['is_favorite'] ?? false,
                'status' => PrintJobStatusEnum::Pending,
            ]);
        });

        broadcast(new PrintJobCreated($job));

        return $job;
    }

    /**
     * Reclama de forma atómica el siguiente trabajo pendiente en la cola.
     *
     * Emplea un bloqueo pesimista `lockForUpdate` para garantizar exclusión mutua
     * ante múltiples microcontroladores o hilos de sondeo concurrentes.
     */
    public function claimNextJob(Printer $printer): ?PrinterStack
    {
        $job = DB::transaction(function () use ($printer) {
            /** @var PrinterStack|null $nextJob */
            $nextJob = PrinterStack::where('printer_id', $printer->id)
                ->where('status', PrintJobStatusEnum::Pending)
                ->orderByDesc('priority')
                ->orderBy('created_at')
                ->lockForUpdate()
                ->first();

            if (! $nextJob) {
                return null;
            }

            $nextJob->status = PrintJobStatusEnum::Processing;
            $nextJob->attempts = $nextJob->attempts + 1;
            $nextJob->save();

            return $nextJob;
        });

        if ($job !== null) {
            $printer->update(['last_seen_at' => Carbon::now()]);
            broadcast(new PrintJobStatusUpdated($job));
        }

        return $job;
    }

    /**
     * Actualiza el estado de un trabajo de impresión tras la ejecución física.
     *
     * Si `status` es `completed`, incrementa el contador de impresiones del trabajo
     * y el odómetro global de la impresora.
     * Si `status` es `out_of_paper`, reencola el trabajo a `pending` y actualiza la impresora.
     */
    public function updateJobStatus(PrinterStack $job, PrintJobStatusEnum|PrinterStatusEnum|string $status, ?string $errorMessage = null): PrinterStack
    {
        $isOutOfPaper = $status === 'out_of_paper' || $status === PrinterStatusEnum::OutOfPaper;
        $statusEnum = $isOutOfPaper
            ? null
            : ($status instanceof PrintJobStatusEnum ? $status : PrintJobStatusEnum::from((string) $status));

        DB::transaction(function () use ($job, $statusEnum, $isOutOfPaper, $errorMessage) {
            $printer = $job->printer;

            if ($isOutOfPaper) {
                $job->status = PrintJobStatusEnum::Pending;
                if ($errorMessage !== null) {
                    $job->error_message = $errorMessage;
                }
                $job->save();

                $printer->update([
                    'status' => PrinterStatusEnum::OutOfPaper,
                    'last_seen_at' => Carbon::now(),
                ]);
            } elseif ($statusEnum === PrintJobStatusEnum::Completed) {
                $job->status = PrintJobStatusEnum::Completed;
                $job->print_count = $job->print_count + 1;
                $job->printed_at = Carbon::now();
                $job->error_message = null;
                $job->save();

                $printer->increment('total_prints_count');
                $printer->update([
                    'status' => PrinterStatusEnum::Ready,
                    'last_seen_at' => Carbon::now(),
                ]);
            } elseif ($statusEnum === PrintJobStatusEnum::Failed) {
                $job->status = PrintJobStatusEnum::Failed;
                $job->error_message = $errorMessage;
                $job->save();

                $printer->update(['last_seen_at' => Carbon::now()]);
            } else {
                $job->status = $statusEnum;
                $job->save();
            }
        });

        broadcast(new PrintJobStatusUpdated($job));

        return $job;
    }

    /**
     * Reencola una copia de un trabajo existente para su reimpresión inmediata.
     */
    public function reprintJob(PrinterStack $job, ?int $priority = null, ?User $user = null): PrinterStack
    {
        $printer = $job->printer;

        $newJob = PrinterStack::create([
            'printer_id' => $job->printer_id,
            'user_id' => $user !== null ? $user->id : $job->user_id,
            'content' => $job->content,
            'format' => $job->format,
            'note' => $job->note ? "[Reimpresión] {$job->note}" : '[Reimpresión]',
            'priority' => $priority ?? $job->priority,
            'attempts' => 0,
            'print_count' => 0,
            'is_favorite' => false,
            'status' => PrintJobStatusEnum::Pending,
        ]);

        broadcast(new PrintJobCreated($newJob));

        return $newJob;
    }

    /**
     * Alterna la marca de plantilla o favorito de un trabajo.
     */
    public function toggleFavorite(PrinterStack $job, ?bool $isFavorite = null): PrinterStack
    {
        $job->is_favorite = $isFavorite !== null ? $isFavorite : ! $job->is_favorite;
        $job->save();

        return $job;
    }

    /**
     * Registra un latido o sondeo de presencia (heartbeat) del microcontrolador.
     */
    public function recordHeartbeat(Printer $printer, PrinterStatusEnum|string $status): void
    {
        $statusEnum = $status instanceof PrinterStatusEnum ? $status : PrinterStatusEnum::from((string) $status);

        $printer->update([
            'status' => $statusEnum,
            'last_seen_at' => Carbon::now(),
        ]);
    }

    /**
     * Cancela un trabajo en cola si aún no ha sido completado.
     */
    public function cancelJob(PrinterStack $job): PrinterStack
    {
        if ($job->status === PrintJobStatusEnum::Completed) {
            throw new RuntimeException('No se puede cancelar un trabajo que ya ha sido completado con éxito.');
        }

        $job->status = PrintJobStatusEnum::Cancelled;
        $job->save();

        broadcast(new PrintJobStatusUpdated($job));

        return $job;
    }

    /**
     * Elimina un trabajo de la cola de impresión.
     */
    public function deleteJob(PrinterStack $job): bool
    {
        return (bool) $job->delete();
    }
}
