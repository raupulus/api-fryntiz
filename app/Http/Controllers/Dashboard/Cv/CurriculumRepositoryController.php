<?php

namespace App\Http\Controllers\Dashboard\Cv;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cv\StoreCvRepositoryRequest;
use App\Models\CV\Curriculum;
use App\Models\CV\CurriculumAvailableRepositoryType;
use App\Models\CV\CurriculumRepository;
use App\Models\File;
use Illuminate\Http\Request;
use function auth;
use function dd;
use function view;

/**
 * Class CurriculumRepositoryController
 */
class CurriculumRepositoryController extends Controller
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

        $repositories = CurriculumRepository::where('curriculum_id', $cv->id)
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        $availableRepositories = CurriculumAvailableRepositoryType::all();

        return view('dashboard.curriculums.repositories.index')->with([
            'cv' => $cv,
            'repositories' => $repositories,
            'availableRepositories' => $availableRepositories,
        ]);
    }

    public function store(StoreCvRepositoryRequest $request, int $cv_id)
    {
        $cv = Curriculum::where('id', $cv_id)
            ->where('user_id', auth()->id())
            ->first();

        if ( !$cv ) {
            return abort(404);
        }

        if ($request->has('repository_id')) {
            $repository = CurriculumRepository::find($request->has('repository_id'));
        } else {
            $repository = new CurriculumRepository([
                'curriculum_id' => $cv->id,
                'user_id' => auth()->id(),
            ]);
        }

        if ($repository->curriculum->user_id !== auth()->id()) {
            return abort(403);
        }

        $repository->fill($request->validated());
        $repository->save();

        ## Compruebo si se ha subido una imagen y la guardo.
        if ($request->hasFile('image')) {
            $file = File::addFile($request->file('image'), 'cv_repository',
                true,
                $cv->image_id);

            if (!$cv->image_id && $file) {
                $cv->image_id = $file->id;
                $cv->save();
            }
        }

        return redirect()->route('dashboard.curriculums.repositories.index', $cv->id);
    }
}
