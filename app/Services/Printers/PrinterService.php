<?php

declare(strict_types=1);

namespace App\Services\Printers;

use App\Enums\PrinterStatusEnum;
use App\Enums\PrintJobFormatEnum;
use App\Enums\PrintJobStatusEnum;
use App\Enums\UserRoleEnum;
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
     * Consulta base de impresoras filtradas por propietario y dispositivos alcanzables.
     *
     * @param  array<int, int>  $allowedDeviceIds
     * @return Builder<Printer>
     */
    public function printersQuery(
        ?int $userId = null,
        array $allowedDeviceIds = [],
        bool $excludeSuperAdminOwned = false
    ): Builder {
        $query = Printer::query()
            ->with(['hardwareDevice'])
            ->withCount([
                'printStack as pending_jobs_count' => fn (Builder $q) => $q->where('status', PrintJobStatusEnum::Pending),
            ]);

        if ($userId !== null) {
            $query->whereHas('hardwareDevice', fn (Builder $q) => $q->where('user_id', $userId));
        } elseif ($excludeSuperAdminOwned) {
            $query->whereHas('hardwareDevice.user', fn (Builder $q) => $q->where('role_id', '!=', UserRoleEnum::SuperAdmin->value));
        }

        if ($allowedDeviceIds !== []) {
            $query->whereIn('hardware_device_id', $allowedDeviceIds);
        }

        return $query;
    }

    /**
     * Obtiene el listado de impresoras paginadas, opcionalmente filtradas por propietario, dispositivos permitidos o atributos.
     *
     * @param  array<string, mixed>  $filters
     * @param  array<int, int>  $allowedDeviceIds
     * @return LengthAwarePaginator<int, mixed>
     */
    public function getPrinters(
        ?int $userId = null,
        array $filters = [],
        int $perPage = 15,
        array $allowedDeviceIds = [],
        bool $excludeSuperAdminOwned = false
    ): LengthAwarePaginator {
        $query = $this->printersQuery($userId, $allowedDeviceIds, $excludeSuperAdminOwned);

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
     * Consulta base del historial/cola de trabajos de una impresora.
     *
     * @return Builder<PrinterStack>
     */
    public function jobsQueryForPrinter(Printer $printer): Builder
    {
        return PrinterStack::query()->where('printer_id', $printer->id);
    }

    /**
     * Consulta base de plantillas favoritas de una impresora.
     *
     * @return Builder<PrinterStack>
     */
    public function favoritesQueryForPrinter(Printer $printer): Builder
    {
        return PrinterStack::query()
            ->where('printer_id', $printer->id)
            ->favorites();
    }

    /**
     * Obtiene el historial o cola de trabajos de una impresora.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, mixed>
     */
    public function getJobsForPrinter(Printer $printer, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = $this->jobsQueryForPrinter($printer);

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
        return $this->favoritesQueryForPrinter($printer)
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
                ->lock(DB::getDriverName() === 'pgsql' ? 'for update skip locked' : true)
                ->first();

            if (! $nextJob) {
                return null;
            }

            $nextJob->status = PrintJobStatusEnum::Processing;
            $nextJob->attempts = $nextJob->attempts + 1;
            $nextJob->save();

            Printer::query()
                ->whereKey($printer->id)
                ->update(['last_seen_at' => Carbon::now()]);

            return $nextJob;
        });

        if ($job !== null) {
            $printer->refresh();
            broadcast(new PrintJobStatusUpdated($job));
        }

        return $job;
    }

    /**
     * Actualiza el estado de un trabajo de impresión tras la ejecución física.
     *
     * Si `status` es `completed`, incrementa el contador de impresiones del trabajo
     * y el odómetro global de la impresora de forma idempotente bajo bloqueo pesimista.
     * Si `status` es `out_of_paper`, reencola el trabajo a `pending` y actualiza la impresora.
     */
    public function updateJobStatus(PrinterStack $job, PrintJobStatusEnum|PrinterStatusEnum|string $status, ?string $errorMessage = null): PrinterStack
    {
        $isOutOfPaper = $status === 'out_of_paper' || $status === PrinterStatusEnum::OutOfPaper;
        $statusEnum = $isOutOfPaper
            ? null
            : ($status instanceof PrintJobStatusEnum ? $status : PrintJobStatusEnum::from((string) $status));

        $changed = false;

        $lockedJob = DB::transaction(function () use ($job, $statusEnum, $isOutOfPaper, $errorMessage, &$changed): PrinterStack {
            /** @var PrinterStack $lockedJob */
            $lockedJob = PrinterStack::query()
                ->whereKey($job->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Reintento idempotente de confirmación final: no duplicar el odómetro ni rebroadcast.
            if ($lockedJob->status === PrintJobStatusEnum::Completed && $statusEnum === PrintJobStatusEnum::Completed) {
                return $lockedJob;
            }

            if (in_array($lockedJob->status, [
                PrintJobStatusEnum::Completed,
                PrintJobStatusEnum::Failed,
                PrintJobStatusEnum::Cancelled,
            ], true)) {
                throw new RuntimeException('No se puede modificar el estado de un trabajo ya finalizado o cancelado.');
            }

            /** @var Printer $printer */
            $printer = Printer::query()
                ->whereKey($lockedJob->printer_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($isOutOfPaper) {
                $lockedJob->status = PrintJobStatusEnum::Pending;
                if ($errorMessage !== null) {
                    $lockedJob->error_message = $errorMessage;
                }
                $lockedJob->save();

                $printer->update([
                    'status' => PrinterStatusEnum::OutOfPaper,
                    'last_seen_at' => Carbon::now(),
                ]);
            } elseif ($statusEnum === PrintJobStatusEnum::Completed) {
                $lockedJob->status = PrintJobStatusEnum::Completed;
                $lockedJob->print_count = $lockedJob->print_count + 1;
                $lockedJob->printed_at = Carbon::now();
                $lockedJob->error_message = null;
                $lockedJob->save();

                $printer->increment('total_prints_count');
                $printer->update([
                    'status' => PrinterStatusEnum::Ready,
                    'last_seen_at' => Carbon::now(),
                ]);
            } elseif ($statusEnum === PrintJobStatusEnum::Failed) {
                $lockedJob->status = PrintJobStatusEnum::Failed;
                $lockedJob->error_message = $errorMessage;
                $lockedJob->save();

                $printer->update(['last_seen_at' => Carbon::now()]);
            } else {
                $lockedJob->status = $statusEnum;
                $lockedJob->save();
            }

            $changed = true;

            return $lockedJob;
        });

        $job->setRawAttributes($lockedJob->getAttributes(), true);
        $job->syncOriginal();

        if ($changed) {
            broadcast(new PrintJobStatusUpdated($lockedJob));
        }

        return $lockedJob;
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
     * Cancela un trabajo en cola si aún no ha sido completado ni fallado.
     */
    public function cancelJob(PrinterStack $job): PrinterStack
    {
        $changed = false;

        $lockedJob = DB::transaction(function () use ($job, &$changed): PrinterStack {
            /** @var PrinterStack $lockedJob */
            $lockedJob = PrinterStack::query()
                ->whereKey($job->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedJob->status === PrintJobStatusEnum::Cancelled) {
                return $lockedJob;
            }

            if (in_array($lockedJob->status, [PrintJobStatusEnum::Completed, PrintJobStatusEnum::Failed], true)) {
                throw new RuntimeException('No se puede cancelar un trabajo que ya ha finalizado.');
            }

            $lockedJob->status = PrintJobStatusEnum::Cancelled;
            $lockedJob->save();
            $changed = true;

            return $lockedJob;
        });

        $job->setRawAttributes($lockedJob->getAttributes(), true);
        $job->syncOriginal();

        if ($changed) {
            broadcast(new PrintJobStatusUpdated($lockedJob));
        }

        return $lockedJob;
    }

    /**
     * Elimina un trabajo de la cola de impresión.
     */
    public function deleteJob(PrinterStack $job): bool
    {
        return (bool) $job->delete();
    }
}
