<?php

namespace App\Models\BaseModels;

use Illuminate\Database\Eloquent\Model;

/**
 * Class BaseModel
 * Modelo mínimo con funciones comunes a todos los modelos.
 *
 * @package App
 */
class BaseModel extends Model
{

    /**
     * Elimina de forma segura este elemento y sus datos asociados.
     *
     * @return bool
     */
    public function safeDelete()
    {
        ## Elimino la imagen asociada y todas las miniaturas.
        if ($this->image) {
            $this->image->safeDelete();
        }

        return $this->delete();
    }
}
