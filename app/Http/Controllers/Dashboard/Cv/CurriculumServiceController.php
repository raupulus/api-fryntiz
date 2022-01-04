<?php

namespace App\Http\Controllers\Dashboard\Cv;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cv\StoreCvServiceRequest;
use App\Models\CV\Curriculum;
use App\Models\CV\CurriculumRepository;
use App\Models\CV\CurriculumService;
use App\Models\File;
use Illuminate\Http\Request;
use function abort;
use function auth;
use function redirect;
use function view;

/**
 * Class CurriculumServiceController
 */
class CurriculumServiceController extends Controller
{
    /**
     * Muestra el listado de todos los tipos de repositorios.
     *
     * @param int       $cv_id       Curriculum Vitae ID
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|never
     */
    public function index(int $cv_id)
    {
        $cv = Curriculum::where('id', $cv_id)
            ->where('user_id', auth()->id())
            ->first();

        if ( !$cv ) {
            return abort(404);
        }

        $services = CurriculumService::where('curriculum_id', $cv->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.curriculums.services.index')->with([
            'cv' => $cv,
            'service' => new CurriculumService(),
            'services' => $services,
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
        $cv = Curriculum::where('id', $cv_id)
            ->where('user_id', auth()->id())
            ->first();

        if ( !$cv ) {
            return abort(404);
        }

        if ($request->has('service_id')) {
            $service = CurriculumService::find($request->get('service_id'));
        } else {
            $service = new CurriculumService([
                'curriculum_id' => $cv->id,
                'user_id' => auth()->id(),
            ]);
        }

        $service->fill($request->validated());
        $service->save();

        ## Compruebo si se ha subido una imagen y la guardo.
        if ($request->hasFile('image')) {
            $file = File::addFile($request->file('image'), 'cv_service',
                true,
                $service->image_id);

            if (!$service->image_id && $file) {
                $service->image_id = $file->id;
                $service->save();
            }
        }

        return redirect()->route('dashboard.cv.service.index', $cv->id);
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
        $service = CurriculumRepository::find($id);

        if ( !$service ) {
            return abort(404);
        }

        $cv = $service->curriculum;

        if ( !$cv || ($cv->user_id !== auth()->id())) {
            return abort(404);
        }

        $services = CurriculumService::where('curriculum_id', $cv->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.curriculums.services.index')->with([
            'cv' => $cv,
            'service' => $service,
            'services' => $services,
        ]);
    }

    /**
     * Guarda los cambios de un repositorio.
     *
     * @param \App\Http\Requests\Cv\StoreCvServiceRequest $request
     * @param                                                $id
     *
     * @return \Illuminate\Http\RedirectResponse|never
     */
    public function update(StoreCvServiceRequest $request, $id)
    {
        $service = CurriculumService::find($id);

        if (!$service) {
            return abort(404);
        }

        $cv = $service->curriculum;

        if ( !$cv || ($cv->user_id !== auth()->id())) {
            return abort(404);
        }

        $service->fill($request->validated());
        $service->save();

        ## Compruebo si se ha subido una imagen y la guardo.
        if ($request->hasFile('image')) {
            $file = File::addFile($request->file('image'), 'cv_repository',
                true,
                $service->image_id);

            if (!$service->image_id && $file) {
                $service->image_id = $file->id;
                $service->save();
            }
        }

        return redirect()->route('dashboard.cv.service.index', $cv->id);
    }

    /**
     * Elimina un repositorio para el usuario actual.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return never|void
     */
    public function destroy(Request $request, int $id)
    {
        $service = CurriculumService::where('id', $id)->first();

        if ( !$service ||
            !$service->curriculum ||
            ($service->curriculum->user_id != auth()->id()))
        {
            return abort(404);
        }

        $cv_id = $service->curriculum_id;

        ## Elimina el repositorio con todos los datos asociados como imágenes.
        $deleted = $service->safeDelete();

        return redirect()->route('dashboard.cv.service.index', $cv_id);
    }
}
