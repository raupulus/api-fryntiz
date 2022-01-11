<?php

namespace App\Http\Controllers\Dashboard\Cv;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cv\StoreCvServiceRequest;
use App\Models\CV\Curriculum;
use App\Models\File;
use Illuminate\Http\Request;
use function abort;
use function auth;
use function redirect;
use function route;
use function view;

/**
 * Class CurriculumServiceController
 */
class CurriculumServiceController extends Controller
{
    public $modelName = '\App\Models\CV\CurriculumService';

    /**
     * Muestra el listado de todos.
     *
     * @param int       $cv_id       Curriculum Vitae ID
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
        if ( !$cv ) {
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
     * Almacena un nuevo repositorio para el usuario actual.
     *
     * @param \App\Http\Requests\Cv\StoreCvServiceRequest $request
     * @param int                                            $cv_id ID del CV
     *
     * @return \Illuminate\Http\RedirectResponse|never
     */
    public function store(StoreCvServiceRequest $request, int $cv_id)
    {
        ## Busco el curriculum para el usuario actual.
        $cv = Curriculum::where('id', $cv_id)
            ->where('user_id', auth()->id())
            ->first();

        if ( !$cv ) {
            return abort(404);
        }

        if ($request->has('model_id')) {
            $model = $this->modelName::find($request->get('model_id'));
        } else {
            $model = new $this->modelName([
                'curriculum_id' => $cv->id,
                'user_id' => auth()->id(),
            ]);
        }

        $model->fill($request->validated());
        $model->save();

        ## Compruebo si se ha subido una imagen y la guardo.
        if ($request->hasFile('image')) {
            $imagePath = ($this->modelName)::$imagePath;

            $file = File::addFile($request->file('image'), $imagePath,
                true,
                $model->image_id);

            if (!$model->image_id && $file) {
                $model->image_id = $file->id;
                $model->save();
            }
        }

        return redirect()->route(($this->modelName)::$routesDashboard['index'], $cv->id);
    }

    /**
     * Muestra la vista para editar un repoitorio.
     *
     * @param int $id ID del repositorio a editar.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|never
     */
    public function edit(int $id)
    {
        $model = $this->modelName::find($id);

        if ( !$model ) {
            return abort(404);
        }

        ## Busco el curriculum para el usuario actual.
        $cv = $model->curriculum;

        if ( !$cv || ($cv->user_id !== auth()->id())) {
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
     * Guarda los cambios de un registro.
     *
     * @param \App\Http\Requests\Cv\StoreCvServiceRequest $request
     * @param                                                $id
     *
     * @return \Illuminate\Http\RedirectResponse|never
     */
    public function update(StoreCvServiceRequest $request, $id)
    {
        $model = $this->modelName::find($id);

        if (!$model) {
            return abort(404);
        }

        ## Busco el curriculum para el usuario actual.
        $cv = $model->curriculum;

        if ( !$cv || ($cv->user_id !== auth()->id())) {
            return abort(404);
        }

        $model->fill($request->validated());
        $model->save();

        ## Compruebo si se ha subido una imagen y la guardo.
        if ($request->hasFile('image')) {
            $imagePath = ($this->modelName)::$imagePath;

            $file = File::addFile($request->file('image'), $imagePath,
                true,
                $model->image_id);

            if (!$model->image_id && $file) {
                $model->image_id = $file->id;
                $model->save();
            }
        }

        return redirect()->route(($this->modelName)::$routesDashboard['index'], $cv->id);
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

        if ( !$model ||
            !$model->curriculum ||
            ($model->curriculum->user_id != auth()->id()))
        {
            return abort(404);
        }

        $cv_id = $model->curriculum_id;

        ## Elimina el repositorio con todos los datos asociados como imágenes.
        $deleted = $model->safeDelete();

        return redirect()->route(($this->modelName)::$routesDashboard['index'], $cv_id);
    }
}
