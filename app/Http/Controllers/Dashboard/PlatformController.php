<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseWithTableCrudController;
use App\Http\Requests\Dashboard\Platform\PlatformDeleteRequest;
use App\Http\Requests\Dashboard\Platform\PlatformStoreRequest;
use App\Http\Requests\Dashboard\Platform\PlatformUpdateRequest;
use App\Models\Category;
use App\Models\File;
use App\Models\Platform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
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
     * @return View
     */
    public function index(): View
    {
        return view('dashboard.' . self::getModel()::getModuleName() . '.index')->with([
            'model' => self::getModel(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return View
     */
    public function create(): View
    {
        $model = new (self::getModel())();

        return view('dashboard.' . $model::getModuleName() . '.add-edit')->with([
            'model' => $model,
            'categories' => Category::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param PlatformStoreRequest $request
     *
     * @return RedirectResponse
     */
    public function store(PlatformStoreRequest $request): RedirectResponse
    {
        $modelString = $this::getModel();
        $model = $modelString::create($request->validated());

        ## Guarda la imagen desde base64
        if ($request->has('image') && $request->get('image')) {
            $image = File::addFileFromBase64($request->get('image'), 'platform', false, $model->image?->id);

            if ($image) {
                $model->image_id = $image->id;
                $model->save();

                $image->title = $model->title;
                $image->alt = $model->title;
                $image->save();
            }
        }


        //TODO: Unificar esta parte con la del update si se ve rentable en tiempo

        $subcategories = [];
        $categories = $request->get('categories') ?? [];

        foreach ($categories as $category) {
            $categoryModel = Category::find($category);

            if ($categoryModel) {
                $subcategories = array_merge($subcategories, $categoryModel->subcategories->pluck('id')->toArray());
            }
        }

        $categoriesWithSubcategories = array_merge($categories, $subcategories);

        foreach ($categoriesWithSubcategories as $id) {
            $model->categories()->attach($id, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }


        return redirect()->route($modelString::getCrudRoutes()['index']);
    }

    /**
     * Display the specified resource.
     *
     * @param Platform $platform
     */
    public function show(Platform $platform)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Platform $model
     *
     * @return View
     */
    public function edit(Platform $model): View
    {
        return view('dashboard.' . self::getModel()::getModuleName() . '.add-edit')->with([
            'model' => $model,
            'categories' => Category::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param PlatformUpdateRequest $request
     * @param Platform $model
     *
     * @return RedirectResponse
     */
    public function update(PlatformUpdateRequest $request, Platform $model): RedirectResponse
    {

        $model->fill($request->validated());
        $model->save();

        ## Guarda la imagen desde base64
        if ($request->has('image') && $request->get('image')) {
            $image = File::addFileFromBase64($request->get('image'), 'platform', false, $model->image?->id);

            if ($image) {
                $model->image_id = $image->id;
                $model->save();

                $image->title = $model->title;
                $image->alt = $model->title;
                $image->save();
            }
        }

        //TODO: Unificar esta parte con la del store si se ve rentable en tiempo

        $subcategories = [];
        $categories = $request->get('categories') ?? [];

        foreach ($categories as $category) {
            $categoryModel = Category::find($category);

            if ($categoryModel) {
                $subcategories = array_merge($subcategories, $categoryModel->subcategories->pluck('id')->toArray());
            }
        }

        $categoriesWithSubcategories = array_merge($categories, $subcategories);

        $currentIds = $model->categories->pluck('id')->toArray();

        ## IDs a eliminarse
        $idsToRemove = array_diff($currentIds, $categoriesWithSubcategories);

        ## IDs para añadirse
        $idsToAdd = array_diff($categoriesWithSubcategories, $currentIds);

        ## Elimino relaciones que no están en los nuevos IDs
        if (!empty($idsToRemove)) {
            $model->categories()->detach($idsToRemove);
        }

        foreach ($idsToAdd as $id) {
            $model->categories()->attach($id, [
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route(Platform::getCrudRoutes()['index']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param PlatformDeleteRequest $request
     * @param int|null $id
     *
     * @return JsonResponse|RedirectResponse
     */
    public function destroy(PlatformDeleteRequest $request, int|null $id = null): JsonResponse|RedirectResponse
    {

        // TODO: Revisar si recibir ID o inyectar modelo Platform


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
