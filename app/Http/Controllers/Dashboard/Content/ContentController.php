<?php

namespace App\Http\Controllers\Dashboard\Content;

use App\Http\Controllers\BaseWithTableCrudController;
use App\Http\Requests\Dashboard\Content\ContentDeleteRequest;
use App\Http\Requests\Dashboard\Content\ContentStoreRequest;
use App\Http\Requests\Dashboard\Content\ContentUpdateRequest;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailableType;
use App\Models\Content\ContentPage;
use App\Models\Platform;
use App\Models\PlatformTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use JsonHelper;

/**
 * Class ContentAvailableCategoryController
 *
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
    public function index(Platform $platform = null, $slug = null)
    {
        return view('dashboard.' . self::getModel()::getModuleName() . '.index')->with([
            'model' => self::getModel(),
            'platform' => $platform,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create(Request $request, Platform $platform)
    {
        $model = new (self::getModel())();
        $model->platform_id = $platform->id;

        $contributorsIds = $model->contributors->pluck('id')->toArray();

        return view('dashboard.' . $model::getModuleName() . '.add-edit')->with([
            'model' => $model,
            'users' => User::all(),
            'platforms' => Platform::all(),
            'contentTypes' => ContentAvailableType::all(),
            'contributorsIds' => $contributorsIds,
            'tags' => $model->platform->tags,
            'categories' => $model->platform->categories,
            'modelCategoriesIds' => $model->categories->pluck('id')->toArray(),
            'modelTagsIds' => $model->tags->pluck('id')->toArray(),
            'contentRelatedAll' => collect(),
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


        if (isset($requestValidated['contributors'])) {
            $model->saveContributors($requestValidated['contributors']);
        }

        if (isset($requestValidated['tags'])) {
            $model->saveTags($requestValidated['tags']);
        }

        if (isset($requestValidated['categories'])) {
            $model->saveCategories($requestValidated['categories']);
        }


        // TODO: Crear trait? Para imágenes y dinamizar?

        dd($model);


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
     * @param \App\Models\Content\Content $model
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Content $model)
    {
        $contributorsIds = $model->contributors->pluck('id')->toArray();

        $contentRelated = $model->contentsRelated;
        $contentRelatedMe = $model->contentsRelatedMe;

        $contentRelatedAll = $contentRelated->merge($contentRelatedMe);

        $pages = $model->pages;

        if (!$pages || !$pages->count()) {
            $pages = collect([
                new ContentPage([
                    'content_id' => $model->id,
                    'title' => 'Página Principal',
                    'slug' => Str::slug($model->title),
                    'content' => '',
                    'order' => 1,
                ])
            ]);
        }


        return view('dashboard.' . self::getModel()::getModuleName() . '.add-edit')->with([
            'model' => $model,
            'pages' => $pages,
            'users' => User::all(), // TODO: Pasar a ajax desde el frontend
            'platforms' => Platform::all(),
            'contentTypes' => ContentAvailableType::all(),
            'contributorsIds' => $contributorsIds,
            'tags' => $model->platform->tags,
            'categories' => $model->platform->categories,
            'modelCategoriesIds' => $model->categoriesQuery()->pluck('id')->toArray(),
            'modelTagsIds' => $model->tagsQuery()->pluck('id')->toArray(),
            'contentRelatedAll' => $contentRelatedAll,
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \App\Http\Requests\Dashboard\Content\ContentUpdateRequest $request
     * @param int|null $id
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
     * @param int|null $id
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

    public function ajaxGetSelectInfoFromPlataform(Request  $request,
                                                   Platform $platform)
    {
        $contentId = $request->get('contentId');
        $content = Content::find($contentId);

        if ($content) {
            $contentRelated = $content->contentsRelatedAllPlatforms()
                ->where('contents.platform_id', $platform->id)
                ->get();

            $contentRelatedMe = $content->contentsRelatedMeAllPlatforms()
                ->where('contents.platform_id', $platform->id)
                ->get();
        } else {
            $contentRelated = collect();
            $contentRelatedMe = collect();
        }

        $contentRelatedAll = $contentRelated->merge($contentRelatedMe);

        $tagsSelected = $content ? $content->tagsQuery()->select(['tags.id', 'tags.name'])->get() : collect();
        $categoriesSelected = $content ? $content->categoriesQuery()->select
        (['categories.id', 'categories.name'])->get() : collect();

        $tags = $platform->tags()->select(['tags.id', 'tags.name'])->get();
        $categories = $platform->categories()->select(['categories.id', 'categories.name'])->get();

        return JsonHelper::accepted([
            'contentsRelated' => $contentRelatedAll,
            'tags' => $tags,
            'categories' => $categories,
            'tagsSelected' => $tagsSelected,
            'categoriesSelected' => $categoriesSelected,
        ]);
    }

    /**
     * Devuelve el contenido relacionado a partir del patrón de búsqueda
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Platform $platform
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ajaxGetContentRelatedFiltered(Request  $request,
                                                  Platform $platform)
    {
        $contentRelatedSearch = $request->get('content_related_search');
        $contentId = $request->get('contentId');
        $content = Content::find($contentId);


        $contentRelated = $content ? $content->contentsRelated : collect();
        $contentRelatedMe = $content ? $content->contentsRelatedMe : collect();
        $contentRelatedAll = $contentRelated->merge($contentRelatedMe);


        $contents = $platform->contents()
            ->select(['contents.id', 'contents.title']);

        if ($contentRelatedAll && $contentRelatedAll->count()) {
            $contents->whereNotIn('contents.id', clone ($contentRelatedAll)->pluck
            ('id'));
        }

        if ($contentRelatedSearch) {
            $contents->where('contents.title', 'like', '%' .
                $contentRelatedSearch . '%');
        }

        $contents = $contents->limit(20)->get();

        return JsonHelper::accepted([
            'contents' => $contents,
            'contentsRelated' => $contentRelatedAll,
        ]);
    }

    public function ajaxTagCreate(Request $request)
    {
        $tagsName = $request->get('tagNames');
        $platformId = $request->get('platform_id');
        $platform = Platform::find($platformId);

        if (!$platform) {
            return JsonHelper::error('Plataforma no encontrada');
        }

        $tags = [];

        $slugs = $platform->tags()->pluck('slug')->toArray();

        foreach ($tagsName as $tagName) {
            $name = trim($tagName);
            $slug = Str::slug($tagName);

            if (!in_array($slug, $slugs)) {
                $tag = Tag::firstOrCreate([
                    'slug' => $slug,
                ], [
                    'name' => $name,
                ]);

                $tags[] = $tag;

                PlatformTag::firstOrCreate([
                    'platform_id' => $platformId,
                    'tag_id' => $tag->id,
                ]);
            }
        }

        return JsonHelper::accepted([
            'tags' => $tags,
        ]);

    }
}
