<?php

declare(strict_types=1);

namespace App\Actions;

use Illuminate\Database\Eloquent\Model;

/**
 * Acción reutilizable para almacenar de forma genérica datos de sensores en sus modelos correspondientes.
 */
class StoreSensorDataAction
{
    /**
     * Ejecuta el guardado de un único registro en la base de datos de forma atómica.
     *
     * @param  string  $modelClass  Nombre de la clase/namespace del modelo Eloquent a instanciar.
     * @param  array  $data  Arreglo asociativo con los valores a insertar.
     * @return Model El registro recién creado.
     */
    public function execute(string $modelClass, array $data): Model
    {
        return $modelClass::create($data);
    }

    /**
     * Ejecuta el guardado masivo (por lotes) de varios registros para un mismo tipo de modelo.
     *
     * @param  string  $modelClass  Nombre de la clase/namespace del modelo Eloquent.
     * @param  array  $records  Arreglo multidimensional de registros a insertar.
     * @return array Colección en formato arreglo de los modelos creados.
     */
    public function executeBatch(string $modelClass, array $records): array
    {
        $stored = [];
        foreach ($records as $record) {
            $stored[] = $modelClass::create($record);
        }

        return $stored;
    }
}
