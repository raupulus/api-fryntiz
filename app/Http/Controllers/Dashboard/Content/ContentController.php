<?php

namespace App\Http\Controllers\Dashboard\Content;

use App\Http\Controllers\BaseWithTableCrudController;
use App\Http\Requests\Dashboard\Content\ContentDeleteRequest;
use App\Http\Requests\Dashboard\Content\ContentEditRequest;
use App\Http\Requests\Dashboard\Content\ContentStoreRequest;
use App\Http\Requests\Dashboard\Content\ContentUpdateRequest;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Platform;
use App\Models\User;
use Illuminate\Support\Facades\Request;
use JsonHelper;

/**
 * Class ContentAvailableCategoryController
 * @package App\Http\Controllers\Dashboard\Content
 */
class ContentController extends BaseWithTableCrudController
{
    protected static function getModel(): string
    {
        return Content::class;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('dashboard.' . self::getModel()::getModuleName() . '.index')->with([
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


        $contributorsIds = $model->contributors->pluck('id')->toArray();

        return view('dashboard.' . $model::getModuleName() . '.add-edit')->with([
            'model' => $model,
            'users' => User::all(),
            'platforms' => Platform::all(),
            'contentTypes' => ContentAvailableType::all(),
            'contributorsIds' => $contributorsIds,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Dashboard\Content\ContentStoreRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ContentStoreRequest $request)
    {
        //dd($request->all(), $request->validated());
        $modelString = $this::getModel();
        $requestValidated = $request->validated();
        $model = $modelString::create($requestValidated);

        //'processed_at' => 'nullable|date', // Se comprueba en el controlador
        //'published_at' => 'nullable|date', // Se comprueba en el controlador

        //'contentRelated' => 'nullable|array', //Check ids
        //'tags' => 'nullable|array', //Check ids
        //'categories' => 'nullable|array', //Check ids



        if (isset($requestValidated['contributors'])) {
            $model->saveContributors($requestValidated['contributors']);
        }



        // TODO: Crear trait? Para imágenes y dinamizar?

        //dd($model);


        return redirect()->route($modelString::getCrudRoutes()['index']);
    }

    /**
     * Display the specified resource.
     *
     * @param Content $model
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function show(Content $model)
    {
        return view('dashboard.' . $model::getModuleName() . '.show')->with([
            'model' => $model,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Content\Content         $model
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Content $model)
    {
        $contributorsIds = $model->contributors->pluck('id')->toArray();

        return view('dashboard.' . self::getModel()::getModuleName() . '.add-edit')->with([
            'model' => $model,
            'users' => User::all(), // TODO: Pasar a ajax desde el frontend
            'platforms' => Platform::all(),
            'contentTypes' => ContentAvailableType::all(),
            'contributorsIds' => $contributorsIds,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Dashboard\Content\ContentUpdateRequest $request
     * @param int|null                                               $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ContentUpdateRequest $request, int|null $id = null)
    {
        $modelString = $this::getModel();
        $model = $modelString::find($id);

        $model->fill($request->validated());
        $model->save();

        return redirect()->route($modelString::getCrudRoutes()['index']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Dashboard\Content\ContentDeleteRequest $request
     * @param int|null                                               $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(ContentDeleteRequest $request, int|null $id = null)
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
