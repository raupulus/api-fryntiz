<?php

namespace App\Http\Controllers\Dashboard\Cv;

use App\Http\Controllers\Controller;
use App\Models\CV\CurriculumAvailableRepositoryType;
use App\Models\CV\CurriculumRepository;
use Illuminate\Http\Request;
use function auth;
use function view;

/**
 * Class CurriculumRepositoryController
 */
class CurriculumRepositoryController extends Controller
{
    /**
     * Muestra el listado de todos los tipos de repositorios.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $repositories = CurriculumRepository::where('user_id', auth()->id())
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.curriculums.repositories.index')->with([
            'repositories' => $repositories,
        ]);
    }
}
