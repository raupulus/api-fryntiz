<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use JsonHelper;
use function array_keys;
use function redirect;
use function view;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        return view('dashboard.tags.add-edit');
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
     * @param                          $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $id = null)
    {
        $deleted = false;
        $tag_id = $request->get('id');
        $tag = Tag::find($tag_id);

        if ($tag) {
            $deleted = $tag->safeDelete();
        }

        if ($request->isJson()) {
            return JsonHelper::success(['deleted' => true]);
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

        $data = Tag::getTableQuery($page, $size, $orderBy, $orderDirection);

        return JsonHelper::success([
            'data' => $data,
        ]);
    }

    public function ajaxTableActions(Request $request)
    {

    }
}
