<?php

namespace App\Http\Controllers;

use App\Http\Interfaces\TableCrudControllerInterface;

/**
 * Class Controller
 *
 * @package App\Http\Controllers
 */
abstract class BaseWithTableCrudController extends Controller
{
    /**
     * Comprueba si puede editar un atributo concreto o en general.
     *
     * @return void
     */
    protected function checkCanEdit($attribute = null) {
        $policy = $this->getPolicy();
        $attributes = 'TODO → Obtener atributos';
    }

    /**
     * Devuelve el nombre con el espacio de nombre para el modelo asociado.
     *
     * @return string
     */
    abstract protected function getModel(): string;

    /**
     * Devuelve el nombre con el espacio de nombre para la policy asociada.
     *
     * @return string
     */
    abstract protected function getPolicy(): string;
}
