<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseWithTableCrudController;
use App\Http\Requests\Dashboard\Technology\TechnologyDeleteRequest;
use App\Http\Requests\Dashboard\Technology\TechnologyStoreRequest;
use App\Http\Requests\Dashboard\Technology\TechnologyUpdateRequest;
use App\Models\File;
use App\Models\Technology;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TechnologyController extends BaseWithTableCrudController
{
    protected static function getModel(): string
    {
        return Technology::class;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('dashboard.'.self::getModel()::getModuleName().'.index')->with([
            'model' => self::getModel(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $model = new (self::getModel())();

        return view('dashboard.'.$model::getModuleName().'.add-edit')->with([
            'model' => $model,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TechnologyStoreRequest $request): RedirectResponse
    {
        $modelString = $this::getModel();
        $model = $modelString::create($request->validated());

        // # Guarda la imagen desde base64
        if ($model && $request->has('image') && $request->get('image')) {
            $image = File::addFileFromBase64($request->get('image'), 'technology', false, $model->image?->id);

            if ($image) {
                $model->image_id = $image->id;
                $model->save();

                $image->title = $model->name;
                $image->alt = $model->name;
                $image->save();
            }
        }

        return redirect()->route($modelString::getCrudRoutes()['index']);
    }

    /**
     * Display the specified resource.
     *
     *
     * @return Response
     */
    public function show(Technology $technology)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Technology $technology): View
    {
        return view('dashboard.'.self::getModel()::getModuleName().'.add-edit')->with([
            'model' => $technology,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TechnologyUpdateRequest $request, ?int $id = null): RedirectResponse
    {
        $modelString = $this::getModel();
        $model = $modelString::find($id);

        $model->fill($request->validated());
        $model->save();

        // # Guarda la imagen desde base64
        if ($request->has('image') && $request->get('image')) {
            $image = File::addFileFromBase64($request->get('image'), 'technology', false, $model->image?->id);

            if ($image) {
                $model->image_id = $image->id;
                $model->save();

                $image->title = $model->name;
                $image->alt = $model->name;
                $image->save();
            }
        }

        return redirect()->route($modelString::getCrudRoutes()['index']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TechnologyDeleteRequest $request, ?int $id = null): JsonResponse|RedirectResponse
    {
        $deleted = false;
        $idRequest = $request->get('id');
        $model = self::getModel()::find($idRequest);

        if ($model) {
            $deleted = $model->safeDelete();
        }

        if ($request->isJson()) {
            return \JsonHelper::success(['deleted' => $deleted]);
        }

        return redirect()->back();
    }
}
