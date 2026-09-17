<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Printers;

use App\Enums\PrintJobFormatEnum;
use App\Enums\PrintJobStatusEnum;
use App\Models\PrinterStack;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serialización de trabajos en cola de impresión en API V2.
 *
 * @mixin PrinterStack
 */
class PrintJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'printer_id' => $this->printer_id,
            'user_id' => $this->user_id,
            'status' => $this->status instanceof PrintJobStatusEnum ? $this->status->value : $this->status,
            'format' => $this->format instanceof PrintJobFormatEnum ? $this->format->value : $this->format,
            'content' => $this->content,
            'note' => $this->note,
            'priority' => $this->priority,
            'attempts' => $this->attempts,
            'print_count' => $this->print_count,
            'is_favorite' => $this->is_favorite,
            'error_message' => $this->error_message,
            'printed_at' => $this->printed_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
