<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Printers;

use App\Enums\PrinterStatusEnum;
use App\Enums\PrinterTypeEnum;
use App\Enums\PrintJobFormatEnum;
use App\Models\Printer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para serialización de impresoras en API V2.
 *
 * @mixin Printer
 */
class PrinterResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'hardware_device_id' => $this->hardware_device_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'printer_type' => $this->printer_type instanceof PrinterTypeEnum ? $this->printer_type->value : $this->printer_type,
            'is_active' => $this->is_active,
            'status' => $this->status instanceof PrinterStatusEnum ? $this->status->value : $this->status,
            'supported_formats' => $this->supported_formats,
            'default_format' => $this->default_format instanceof PrintJobFormatEnum ? $this->default_format->value : $this->default_format,
            'max_payload_kb' => $this->max_payload_kb,
            'total_prints_count' => $this->total_prints_count,
            'pending_jobs_count' => $this->when(isset($this->pending_jobs_count), fn () => (int) $this->pending_jobs_count),
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
