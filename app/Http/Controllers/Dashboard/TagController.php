<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseWithTableCrudController;
use App\Http\Requests\Dashboard\Tag\TagDeleteRequest;
use App\Http\Requests\Dashboard\Tag\TagStoreRequest;
use App\Http\Requests\Dashboard\Tag\TagUpdateRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;
use JsonHelper;

use function redirect;
use function view;

/**
 * Controlador para Tags
 */
class TagController extends BaseWithTableCrudController
{
    protected static function getModel(): string
    {
        return Tag::class;
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
    public function store(TagStoreRequest $request): RedirectResponse
    {
        $modelString = $this::getModel();
        $modelString::create($request->validated());

        return redirect()->route($modelString::getCrudRoutes()['index']);
    }

    /**
     * Display the specified resource.
     *
     *
     * @return Response
     */
    public function show(Tag $tag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Tag $model): View
    {
        return view('dashboard.'.self::getModel()::getModuleName().'.add-edit')->with([
            'model' => $model,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TagUpdateRequest $request, ?int $id = null): RedirectResponse
    {
        $modelString = $this::getModel();
        $model = $modelString::find($id);

        $model->fill($request->validated());
        $model->save();

        return redirect()->route($modelString::getCrudRoutes()['index']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TagDeleteRequest $request, ?int $id = null): RedirectResponse
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

    // ###########################################################
    // #                       AJAX                             ##
    // ###########################################################

}
