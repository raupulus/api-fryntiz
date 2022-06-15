<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\BaseWithTableCrudController;
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
    protected function getModel() : string {
        return Tag::class;
    }

    protected function getPolicy(): string
    {
        return 'FALTA CREARLA!!!';
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $tags = Tag::all();

        return view('dashboard.tags.index')->with([
            'tags' => $tags,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create()
    {
        return view('dashboard.tags.add-edit');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $slug
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(string $slug)
    {
        $tag = Tag::where('slug', $slug)->first();

        return view('dashboard.tags.add-edit')->with([
            'tag' => $tag
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int|null                 $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, int $id = null)
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

    /**
     * Devuelve todas las etiquetas preparadas para mostrarlas en una tabla.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxTableGetQuery(Request $request)
    {
        $page = $request->json('page');
        $size = $request->json('size');
        $orderBy = $request->json('orderBy');
        $orderDirection = $request->json('orderDirection');
        $search = $request->json('search');

        $data = Tag::getTableQuery($page, $size, $orderBy, $orderDirection, $search);

        return JsonHelper::success([
            'data' => $data,
        ]);
    }

    public function ajaxTableActions(Request $request)
    {

    }
}
