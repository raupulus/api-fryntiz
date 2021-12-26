<?php

namespace App\Http\Controllers\Dashboard\Cv;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCvRequest;
use App\Models\CV\Curriculum;
use App\Models\CV\CurriculumAvailableRepositoryType;
use App\Models\File;
use Illuminate\Http\Request;
use function auth;
use function redirect;
use function view;

/**
 * Class CurriculumAvailableRepositoryTypeController
 */
class CurriculumAvailableRepositoryTypeController extends Controller
{
    /**
     * Muestra el listado de todos los tipos de repositorios.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {

        // TODO → Check if is admin and can modify repositories type

        $repositoryTypes = CurriculumAvailableRepositoryType::all();

        return view('dashboard.curriculums.repository-available')->with([
            'repositoryTypes' => $repositoryTypes,
        ]);
    }

    /**
     * Muestra el formulario para crear un tipo de repositorio.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        // TODO → Check if is admin and can modify repositories type

        $repositoryType = new CurriculumAvailableRepositoryType();

        return view('dashboard.curriculums.repository-available-add-edit')->with([
            'repositoryType' => $repositoryType,
        ]);
    }

    /**
     * Guarda un tipo de repositorio en la base de datos.
     *
     * @param \App\Http\Requests\StoreCvRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreCvRequest $request)
    {
        // TODO → Check if is admin and can modify repositories type

        ## Compruebo si está editando o creando un curriculum.
        if ($request->has('repositoryType_id') && ($request->get('repositoryType_id') > 0)) {
            $repositoryType = CurriculumAvailableRepositoryType::find($request->get('repositoryType_id'));
        } else {
            $repositoryType = new CurriculumAvailableRepositoryType();
        }

        ## Guardo todos los campos que han pasado validación.
        $repositoryType->fill($request->validated());
        $repositoryType->save();

        ## Compruebo si se ha subido una imagen y la guardo.
        if ($request->hasFile('image')) {
            $file = File::addFile($request->file('image'), 'cv_repository_type',
                false,
                $repositoryType->image_id);

            if (!$repositoryType->image_id && $file) {
                $repositoryType->image_id = $file->id;
                $repositoryType->save();
            }
        }

        return redirect()->route('dashboard.curriculums.repository-available');
    }

    /**
     * Muestra un formulario para editar un tipo de repositorio.
     *
     * @param int $id
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(int $id)
    {
        // TODO → Check if is admin and can modify repositories type

        $repositoryType = CurriculumAvailableRepositoryType::find($id);

        if ( ! $repositoryType) {
            return redirect()->back()->with('error', 'Curriculum not found.');
        }

        return view('dashboard.curriculums.repository-available-add-edit')->with([
            'repositoryType' => $repositoryType,
        ]);
    }

    /**
     * Procesa el guardado de las modificaciones sobre un tipo de repositorio.
     *
     * @param \App\Http\Requests\StoreCvRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(StoreCvRequest $request)
    {
        // TODO → Check if is admin and can modify repositories type

        return $this->store($request);
    }

    /**
     * Elimina un tipo de repositorio.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        // TODO → Check if is admin and can modify repositories type

        $id = $request->post('id');

        if (! $id) {
            return redirect()->back()->with('error', 'Curriculum not found.');
        }

        $repositoryType = CurriculumAvailableRepositoryType::find($id);

        if (!$repositoryType) {
            return redirect()->back()->with('error', 'Curriculum not found.');
        }

        $deleted = $repositoryType->safeDelete();

        return redirect()->route('dashboard.curriculums.repository-available');
    }
}
