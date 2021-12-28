<?php

namespace App\Http\Controllers\Dashboard\Cv;

use App\Http\Controllers\Controller;
use App\Models\CV\Curriculum;
use App\Models\CV\CurriculumAvailableRepositoryType;
use App\Models\CV\CurriculumRepository;
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
}
