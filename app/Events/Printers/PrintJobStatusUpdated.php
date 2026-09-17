<?php

declare(strict_types=1);

namespace App\Events\Printers;

use App\Models\PrinterStack;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Carbon;

/**
 * Evento emitido al actualizarse el estado de un trabajo de impresión.
 *
 * Canal privado: printer.{id}
 * Nombre en broadcast: job.updated
 */
final class PrintJobStatusUpdated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public readonly int $printerId;

    public readonly int $jobId;

    public readonly string $status;

    public readonly int $printCount;

    public readonly string $updatedAt;

    public function __construct(PrinterStack $job)
    {
        $this->printerId = (int) $job->printer_id;
        $this->jobId = (int) $job->id;
        $this->status = $job->status->value;
        $this->printCount = (int) $job->print_count;
        $this->updatedAt = ($job->updated_at ?? Carbon::now())->toISOString();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('printer.'.$this->printerId);
    }

    public function broadcastAs(): string
    {
        return 'job.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'printer_id' => $this->printerId,
            'job_id' => $this->jobId,
            'status' => $this->status,
            'print_count' => $this->printCount,
            'updated_at' => $this->updatedAt,
        ];
    }
}
