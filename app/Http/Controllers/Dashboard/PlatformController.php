<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseWithTableCrudController;
use App\Http\Requests\Dashboard\Platform\PlatformDeleteRequest;
use App\Http\Requests\Dashboard\Platform\PlatformStoreRequest;
use App\Http\Requests\Dashboard\Platform\PlatformUpdateRequest;
use App\Models\Platform;
use Illuminate\Http\Request;
use JsonHelper;
use function redirect;
use function view;

/**
 * Controlador para Plataformas.
 */
class PlatformController extends BaseWithTableCrudController
{
    protected static function getModel(): string
    {
        return Platform::class;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('dashboard.' . self::getModel()::MODULE_NAME . '.index')->with([
            'model' => self::getModel(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        $model = new (self::getModel())();

        return view('dashboard.' . $model::MODULE_NAME . '.add-edit')->with([
            'model' => $model,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Dashboard\Platform\PlatformStoreRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(PlatformStoreRequest $request)
    {
        $modelString = $this::getModel();
        $modelString::create($request->validated());



        // TODO: Crear trait? Para imágenes y dinamizar?


        return redirect()->route($modelString::CRUD_ROUTES['index']);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Platform $model
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Platform $model)
    {
        return view('dashboard.' . self::getModel()::MODULE_NAME . '.add-edit')->with([
            'model' => $model,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Dashboard\Platform\PlatformUpdateRequest $request
     * @param int|null                                               $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(PlatformUpdateRequest $request, int|null $id = null)
    {
        $modelString = $this::getModel();
        $model = $modelString::find($id);

        $model->fill($request->validated());
        $model->save();

        return redirect()->route($modelString::CRUD_ROUTES['index']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Dashboard\Platform\PlatformDeleteRequest $request
     * @param int|null                                               $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(PlatformDeleteRequest $request, int|null $id = null)
    {
        $deleted = false;
        $idRequest = $request->get('id');
        $model = self::getModel()::find($idRequest);

        if ($model) {
            $deleted = $model->safeDelete();
        }

        if ($request->isJson()) {
            return JsonHelper::success(['deleted' => $deleted]);
        }

        return redirect()->back();
    }


    ############################################################
    ##                       AJAX                             ##
    ############################################################

}
