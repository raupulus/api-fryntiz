<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseWithTableCrudController;
use App\Http\Requests\Dashboard\Category\CategoryDeleteRequest;
use App\Http\Requests\Dashboard\Category\CategoryStoreRequest;
use App\Http\Requests\Dashboard\Category\CategoryUpdateRequest;
use App\Models\Category;
use App\Models\Content\Content;
use Illuminate\Http\JsonResponse;
use JsonHelper;
use function redirect;
use function view;

/**
 * Controlador para Categorías
 */
class CategoryController extends BaseWithTableCrudController
{
    protected static function getModel(): string
    {
        return Category::class;
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

        return view('dashboard.' . $model::getModuleName() . '.add-edit')->with([
            'model' => $model,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Dashboard\Category\CategoryStoreRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(CategoryStoreRequest $request)
    {
        $modelString = $this::getModel();

        $modelString::create($request->validated());

        return redirect()->route($modelString::getCrudRoutes()['index']);
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
     * @param \App\Models\Category $model
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Category $model)
    {
        return view('dashboard.' . self::getModel()::getModuleName() . '.add-edit')->with([
            'model' => $model,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Dashboard\Category\CategoryUpdateRequest $request
     * @param int|null                                          $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(CategoryUpdateRequest $request, int|null $id = null)
    {
        $modelString = $this::getModel();
        $model = $modelString::find($id);

        $model->fill($request->validated());
        $model->save();

        //return redirect()->route($modelString::getCrudRoutes()['index']);

        if ($request->has('parent_id') && $request->get('parent_id')) {
            return redirect()->route($modelString::getCrudRoutes()['edit'], $request->get('parent_id'));
        }

        return redirect()->route($modelString::getCrudRoutes()['edit'], $id);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Dashboard\Category\CategoryDeleteRequest $request
     * @param int|null                                          $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(CategoryDeleteRequest $request, int|null $id = null)
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


    /**
     * Devuelve una cadena con el html que corresponde a las subcategorías de la categoría recibida.
     * Utilizado para actualizar los checkbox cuando cambiamos de categoría o plataforma.
     *
     * @param Category $category
     * @return JsonResponse
     *
     * @throws \Throwable
     */
    public function ajaxGetHtmlSubcategories(Category $category, Content $content): JsonResponse
    {
        $html = '';

        foreach ($category->subcategories as $subcategory) {
            $html .= view('dashboard.content.fields._field_subcategory', [
                'subcategory' => $subcategory,
                'category' => $category,
                'content' => $content,
            ])->render();
        }

        return JsonHelper::success([
            'html' => $html,
        ]);
    }
}
