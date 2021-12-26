<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCvRequest;
use App\Models\CV\Curriculum;
use App\Models\File;
use Illuminate\Http\Request;
use function auth;
use function redirect;
use function view;

/**
 * Class CurriculumController
 */
class CurriculumController extends Controller
{
    /**
     * Muestra el listado de todos los curriculums.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $curriculums = Curriculum::where('user_id', auth()->id())
            ->orderByDesc('is_active')
            ->orderByDesc('updated_at')
            ->orderByDesc('created_at')
            ->get();

        return view('dashboard.curriculums.index')->with([
            'curriculums' => $curriculums,
        ]);
    }

    /**
     * Muestra el formulario para crear un curriculum.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $cv = new Curriculum();

        return view('dashboard.curriculums.add-edit')->with([
            'cv' => $cv,
        ]);
    }

    /**
     * Guarda un curriculum en la base de datos.
     *
     * @param \App\Http\Requests\StoreCvRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(StoreCvRequest $request)
    {
        if (auth()->guest()) {
            return redirect()->route('login');
        }

        ## Compruebo si está editando o creando un curriculum.
        if ($request->has('cv_id') && ($request->get('cv_id') > 0)) {
            $cv = Curriculum::find($request->get('cv_id'));
        } else {
            $cv = new Curriculum([
                'user_id' => auth()->id(),
            ]);
        }

        if ($cv->id && ($cv->user_id != auth()->id())) {
            return redirect()->back()->with('error', 'You are not authorized to edit this curriculum.');
        }

        ## Guardo todos los campos que han pasado validación.
        $cv->fill($request->validated());
        $cv->save();

        ## Actualizo los demás curriculums para que solo haya 1 predefinido.
        if ($cv->is_default) {
            Curriculum::where('user_id', auth()->id())
                ->where('id', '!=', $cv->id)
                ->update(['is_default' => false]);
        }

        ## Compruebo si se ha subido una imagen y la guardo.
        if ($request->hasFile('image')) {
            $file = File::addFile($request->file('image'), 'cv', true, $cv->image_id);

            if (!$cv->image_id && $file) {
                $cv->image_id = $file->id;
                $cv->save();
            }
        }

        return redirect()->route('dashboard.cv.index');
    }

    /**
     * Muestra un formulario para editar un curriculum.
     *
     * @param int $id
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(int $id)
    {
        $cv = Curriculum::find($id);

        if ( ! $cv) {
            return redirect()->back()->with('error', 'Curriculum not found.');
        }

        return view('dashboard.curriculums.add-edit')->with([
            'cv' => $cv,
        ]);
    }

    /**
     * Procesa el guardado de las modificaciones sobre un curriculum.
     *
     * @param \App\Http\Requests\StoreCvRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(StoreCvRequest $request)
    {
        return $this->store($request);
    }

    /**
     * Elimina un curriculum.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $id = $request->post('id');

        if (! $id) {
            return redirect()->back()->with('error', 'Curriculum not found.');
        }

        $cv = Curriculum::find($id);

        if (!$cv) {
            return redirect()->back()->with('error', 'Curriculum not found.');
        }

        $deleted = $cv->safeDelete();

        return redirect()->route('dashboard.cv.index');
    }
}
