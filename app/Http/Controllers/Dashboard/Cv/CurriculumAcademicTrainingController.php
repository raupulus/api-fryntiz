<?php

namespace App\Http\Controllers\Dashboard\Cv;

use App\Http\Requests\Cv\StoreCvAcademicTrainingRequest as StoreRequest;
use App\Models\CV\Curriculum;
use App\Models\File;
use function abort;
use function auth;
use function redirect;

/**
 * Class CurriculumServiceController
 */
class CurriculumAcademicTrainingController extends CurriculumBaseSectionController
{
    public $modelName = '\App\Models\CV\CurriculumAcademicTraining';

    /**
     * Almacena un nuevo registro para el usuario actual.
     *
     * @param \App\Http\Requests\Cv\StoreCvAcademicTrainingRequest $request
     * @param int          $cv_id ID del CV
     *
     * @return \Illuminate\Http\RedirectResponse|never
     */
    public function store(StoreRequest $request, int $cv_id)
    {
        ## Busco el curriculum para el usuario actual.
        $cv = Curriculum::where('id', $cv_id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$cv) {
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
     * Guarda los cambios de un registro.
     *
     * @param \App\Http\Requests\Cv\StoreCvAcademicTrainingRequest $request
     * @param                                             $id
     *
     * @return \Illuminate\Http\RedirectResponse|never
     */
    public function update(StoreRequest $request, $id)
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
}
