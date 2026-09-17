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
 * Evento emitido al encolar un nuevo trabajo de impresión.
 *
 * Canal privado: printer.{id}
 * Nombre en broadcast: job.created
 *
 * No transporta el contenido completo del ticket por WebSockets por seguridad
 * y economía de memoria del microcontrolador; sirve como notificación instantánea
 * ("Event-Driven Ping") para que el agente lo reclame vía HTTPS.
 */
final class PrintJobCreated implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;

    public readonly int $printerId;

    public readonly int $jobId;

    public readonly string $format;

    public readonly int $priority;

    public readonly string $createdAt;

    public function __construct(PrinterStack $job)
    {
        $this->printerId = (int) $job->printer_id;
        $this->jobId = (int) $job->id;
        $this->format = $job->format->value;
        $this->priority = (int) $job->priority;
        $this->createdAt = ($job->created_at ?? Carbon::now())->toISOString();
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('printer.'.$this->printerId);
    }

    public function broadcastAs(): string
    {
        return 'job.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'printer_id' => $this->printerId,
            'job_id' => $this->jobId,
            'format' => $this->format,
            'priority' => $this->priority,
            'created_at' => $this->createdAt,
        ];
    }
}
