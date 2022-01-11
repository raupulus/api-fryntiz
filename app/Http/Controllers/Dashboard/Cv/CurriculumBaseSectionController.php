<?php

namespace App\Http\Controllers\Dashboard\Cv;

use App\Http\Controllers\Controller;
use App\Models\CV\Curriculum;
use Illuminate\Http\Request;
use function abort;
use function auth;
use function redirect;
use function route;
use function view;

/**
 * Class CurriculumBaseSectionController
 */
abstract class CurriculumBaseSectionController extends Controller
{
    public $modelName;

    /**
     * Muestra el listado de todos los registros.
     *
     * @param int $cv_id Curriculum Vitae ID
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|never
     */
    public function index(int $cv_id)
    {
        ## Busco el curriculum para el usuario actual.
        $cv = Curriculum::where('id', $cv_id)
            ->where('user_id', auth()->id())
            ->first();

        ## En caso de no existir el curriculum asociado al usuario se aborta.
        if (!$cv) {
            return abort(404);
        }

        ## Busco el modelo asociado ordenado por última modificación.
        $models = $this->modelName::where('curriculum_id', $cv->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        ## Almaceno la ruta hacia la vista desde el modelo.
        $view = ($this->modelName)::$viewsDashboard['index'];

        ## Ruta hacia la acción (Crear o Actualizar).
        $action = route(($this->modelName)::$routesDashboard['store'], $cv->id);

        return view($view)->with([
            'cv' => $cv,
            'modelName' => $this->modelName,
            'model' => new $this->modelName,
            'models' => $models,
            'action' => $action,
        ]);
    }

    /**
     * Muestra la vista para editar un registro.
     *
     * @param int $id ID del repositorio a editar.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|never
     */
    public function edit(int $id)
    {
        $model = $this->modelName::find($id);

        if (!$model) {
            return abort(404);
        }

        ## Busco el curriculum para el usuario actual.
        $cv = $model->curriculum;

        if (!$cv || ($cv->user_id !== auth()->id())) {
            return abort(404);
        }

        $models = $this->modelName::where('curriculum_id', $cv->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        ## Almaceno la ruta hacia la vista desde el modelo.
        $view = ($this->modelName)::$viewsDashboard['index'];

        ## Ruta hacia la acción (Crear o Actualizar).
        $action = route(($this->modelName)::$routesDashboard['update'], $model->id);

        return view($view)->with([
            'cv' => $cv,
            'modelName' => $this->modelName,
            'model' => $model,
            'models' => $models,
            'action' => $action,
        ]);
    }

    /**
     * Elimina un registro para el usuario actual.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return never|void
     */
    public function destroy(Request $request, int $id)
    {
        $model = $this->modelName::where('id', $id)->first();

        if (!$model ||
            !$model->curriculum ||
            ($model->curriculum->user_id != auth()->id())) {
            return abort(404);
        }

        $cv_id = $model->curriculum_id;

        ## Elimina el repositorio con todos los datos asociados como imágenes.
        $deleted = $model->safeDelete();

        return redirect()->route(($this->modelName)::$routesDashboard['index'], $cv_id);
    }
}
