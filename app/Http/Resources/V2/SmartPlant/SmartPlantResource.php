<?php

declare(strict_types=1);

namespace App\Http\Resources\V2\SmartPlant;

use App\Models\SmartPlant\SmartPlantPlant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Resource para el perfil de una planta de Smart Plant en la API V2.
 *
 * Serializa los datos propios de la planta (identidad, cuidados, imagen y
 * fecha de siembra). No incluye sus registros de sensores: esos se sirven
 * por separado con SmartPlantRegisterResource, ya que cada planta puede
 * acumular miles de lecturas y no conviene anidarlas aquí.
 *
 * @mixin SmartPlantPlant
 */
class SmartPlantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'name_scientific' => $this->name_scientific,
            'description' => $this->description,
            'details' => $this->details,
            // Accessor del modelo: resuelve a la imagen por defecto si la planta no tiene una propia asignada.
            'image_url' => $this->url_image,
            'start_at' => $this->start_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
