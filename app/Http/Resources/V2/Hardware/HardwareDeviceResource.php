<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\Hardware;

use App\Models\Hardware\HardwareDevice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para dispositivo hardware en API V2.
 *
 * @mixin HardwareDevice
 */
class HardwareDeviceResource extends JsonResource
{
    /**
     * ¿Se incluye el número de serie? Sólo lo pone `detailed()`.
     */
    private bool $withSerialNumber = false;

    /**
     * ¿Se incluye el último estado conocido? Sólo con `?include=status`.
     */
    private bool $withStatus = false;

    /**
     * Variante de detalle: la única que enseña el número de serie.
     */
    public function detailed(): self
    {
        $this->withSerialNumber = true;

        return $this;
    }

    /**
     * Añade el bloque `status` con el último estado conocido del cacharro:
     * temperatura, tensión, batería, CPU, disco, RAM, uptime, **IP local e IP
     * pública** y lo que traiga en `extra`.
     *
     * Va detrás de `?include=status` y no de serie: son las IPs del aparato, y
     * no tienen por qué viajar en cada respuesta del inventario.
     */
    public function withStatus(): self
    {
        $this->withStatus = true;

        return $this;
    }

    /*
     * Estas relaciones se leen directamente y NO con `whenLoaded()`: quien use
     * este resource tiene que cargarlas con su `with()`.
     *
     * Es una decisión, no un olvido (API-05). `whenLoaded()` haría DESAPARECER
     * la clave del JSON cuando la relación no viene cargada, que es un fallo
     * más silencioso que el que evita: hoy, sin eager load, salta
     * `preventLazyLoading` en local y se ve enseguida. Todos los llamantes
     * actuales cargan lo que hace falta y no hay N+1 real.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'name_friendly' => $this->name_friendly,
            'type' => $this->type,
            'brand' => $this->brand,
            'model' => $this->model,
            'description' => $this->description,
            'hardware_version' => $this->hardware_version,
            'software_version' => $this->software_version,
            // AR-S03: el número de serie sale sólo en el detalle
            // (`GET /hardware/devices/{id}`), que además comprueba el ligado
            // `device:{id}` de la policy. En el listado, un token de cacharro
            // podía barrer los números de serie de todo el parque de su dueño
            // iterando páginas. Es el mismo dato que motivó cerrar el endpoint
            // en la auditoría A3.
            'serial_number' => $this->when($this->withSerialNumber, fn () => $this->serial_number),
            'status' => $this->when(
                $this->withStatus,
                fn () => (new DeviceStatusResource($this->resource))->toArray($request)
            ),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
