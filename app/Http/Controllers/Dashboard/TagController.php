<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseWithTableCrudController;
use App\Http\Requests\Dashboard\Tag\TagDeleteRequest;
use App\Http\Requests\Dashboard\Tag\TagStoreRequest;
use App\Http\Requests\Dashboard\Tag\TagUpdateRequest;
use App\Models\Tag;
use Illuminate\Http\Request;
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
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        //$tags = Tag::all();

        return view('dashboard.tags.index')->with([
            //'tags' => $tags,
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

        return view('dashboard.tags.add-edit')->with([
            'model' => $model,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \App\Http\Requests\Dashboard\Tag\TagStoreRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TagStoreRequest $request)
    {
        $modelString = $this::getModel();
        $modelString::create($request->validated());

        return redirect()->route('dashboard.tag.index');
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
     * @param \App\Models\Tag $tag
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Tag $tag)
    {
        return view('dashboard.tags.add-edit')->with([
            'model' => $tag,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Dashboard\Tag\TagUpdateRequest $request
     * @param int|null                                          $id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TagUpdateRequest $request, int|null $id = null)
    {
        $modelString = $this::getModel();
        $model = $modelString::find($id);

        $model->fill($request->validated());
        $model->save();

        return redirect()->route('dashboard.tag.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Dashboard\Tag\TagDeleteRequest $request
     * @param int|null                                          $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(TagDeleteRequest $request, int|null $id = null)
    {
        $deleted = false;
        $tag_id = $request->get('id');
        $tag = Tag::find($tag_id);

        if ($tag) {
            $deleted = $tag->safeDelete();
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
     * Devuelve todas las etiquetas.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetTags()
    {
        return JsonHelper::success(Tag::all());
    }
}
