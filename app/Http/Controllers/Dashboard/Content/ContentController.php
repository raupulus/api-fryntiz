<?php

namespace App\Http\Controllers\Dashboard\Content;

use App\Helpers\TextFormatParseHelper;
use App\Http\Controllers\BaseWithTableCrudController;
use App\Http\Requests\Dashboard\Content\ContentDeleteRequest;
use App\Http\Requests\Dashboard\Content\ContentStoreRequest;
use App\Http\Requests\Dashboard\Content\ContentUpdateRequest;
use App\Models\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentAvailablePageRaw;
use App\Models\Content\ContentAvailableType;
use App\Models\Content\ContentFile;
use App\Models\Content\ContentPage;
use App\Models\Content\ContentPageRaw;
use App\Models\Content\ContentSeo;
use App\Models\File;
use App\Models\Platform;
use App\Models\PlatformCategory;
use App\Models\PlatformTag;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
     * @return RedirectResponse
     */
    public function store(ContentStoreRequest $request)
    {
        $modelString = $this::getModel();
        $requestValidated = $request->validated();

        $model = $modelString::create($requestValidated);

        $model->pages()->create([
            'title' => $model->title,
            'slug' => Str::slug($model->title . '-' . Str::random(20)),
            'content' => '{}',
            'order' => 1,
        ]);

        if (isset($requestValidated['contents_related'])) {
            $model->contentsRelated()->sync($requestValidated['contents_related']);
        }

        if (isset($requestValidated['contributors'])) {
            $model->saveContributors($requestValidated['contributors']);
        }

        if (isset($requestValidated['tags'])) {
            $model->saveTags($requestValidated['tags']);
        }

        if (isset($requestValidated['categories'])) {
            $model->saveCategories($requestValidated['categories']);
        }

        ## Guarda la imagen en base64
        if ($request->has('image') && $request->get('image')) {
            $image = File::addFileFromBase64($request->get('image'), 'content', false);

            if ($image) {
                $model->image_id = $image->id;
                $model->save();
            }

        }


        ## Genero entrada para SEO

        if ($request->has('image') && $request->get('image')) {
            $seoImage = File::addFileFromBase64($request->get('image'), 'content_seo', false);
        }

        ContentSeo::create([
            'content_id' => $model->id,
            'image_id' => $seoImage->id ?? null,
            'image_alt' => $model->title,
            'keywords' => $model->tags->pluck('name')->implode(', '),
            'description' => $model->excerpt,
            'og_title' => $model->title,
            'og_description' => $model->excerpt,
            'twitter_creator' => $model->author->twitter?->nick,
        ]);

        //return redirect()->route($modelString::getCrudRoutes()['index']);
        return redirect()->to($model->urlEdit);
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
     * @param Content $model
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
     */
    public function update(ContentUpdateRequest $request, Content $content)
    {
        $requestValidated = $request->validated();

        $content->update($requestValidated);

        $content->contentsRelated()->sync($requestValidated['contents_related'] ?? []);
        $content->saveContributors($requestValidated['contributors'] ?? []);
        $content->saveTags($requestValidated['tags'] ?? []);
        $content->saveCategories($requestValidated['categories'] ?? []);

        ## Guarda la imagen en base64
        if ($request->has('image') && $request->get('image')) {
            $image = File::addFileFromBase64($request->get('image'), 'content', false, $content->image?->id);

            if ($image) {
                $content->image_id = $image->id;
                $content->save();
            }
        }

        return redirect()->to($content->urlEdit);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Http\Requests\Dashboard\Content\ContentDeleteRequest $request
     * @param int|null $id
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function destroy(ContentDeleteRequest $request, Content $content = null)
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


    /**
     * Crea una nueva página y lleva a editar contenido, dentro de esta.
     *
     * @param Content $content
     * @return RedirectResponse
     */
    public function addPage(Content $content): RedirectResponse
    {
        $lastPageOrder = ContentPage::where('content_id', $content->id)
            ->max('order');

        $page = ContentPage::create([
            'content_id' => $content->id,
            'title' => uniqid(),
            'slug' => uniqid(),
            'order' => ++$lastPageOrder,
        ]);

        return redirect()->to(route('dashboard.content.edit', $content->id) . '?currentPage=' . $page->id);
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
     * @param Request $request
     * @param Platform $platform
     *
     * @return JsonResponse
     */
    public function ajaxGetContentRelatedFiltered(Request  $request,
                                                  Platform $platform): JsonResponse
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


    public function ajaxTagCreate(Request $request): JsonResponse
    {
        $tagsName = $request->get('words');
        $platformId = $request->get('platform_id');
        $platform = Platform::find($platformId);

        if (!$platform) {
            return JsonHelper::accepted([
                'message' => 'Error al crear las etiquetas.',
                'errors' => [
                    'El id de la plataforma no es válido, se ha eliminado o no existe.',
                ],
            ]);
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

    public function ajaxCategoryCreate(Request $request): JsonResponse
    {

        // TOFIX: He copiado el método para etiquetas, hay que unificar partes en común.

        $tagsName = $request->get('words');
        $platformId = $request->get('platform_id');
        $platform = Platform::find($platformId);

        if (!$platform) {
            return JsonHelper::accepted([
                'message' => 'Error al crear las etiquetas.',
                'errors' => [
                    'El id de la plataforma no es válido, se ha eliminado o no existe.',
                ],
            ]);
        }

        $tags = [];

        $slugs = $platform->categories()->pluck('slug')->toArray();

        foreach ($tagsName as $tagName) {
            $name = trim($tagName);
            $slug = Str::slug($tagName);

            if (!in_array($slug, $slugs)) {
                $tag = Category::firstOrCreate([
                    'slug' => $slug,
                ], [
                    'name' => $name,
                ]);

                $tags[] = $tag;

                PlatformCategory::firstOrCreate([
                    'platform_id' => $platformId,
                    'category_id' => $tag->id,
                ]);
            }
        }

        return JsonHelper::accepted([
            'tags' => $tags,
        ]);

    }

    /**
     * Almacena un archivo recibido por ajax para relacionarlo al contenido.
     *
     * @param Request $request
     * @param Content|null $contentModel
     *
     * @return JsonResponse
     */
    public function ajaxStoreFile(Request $request, Content $contentModel = null): JsonResponse
    {
        $content = $contentModel ?: Content::find($request->get('content_id'));

        if (!$content || !$request->hasFile('file')) {
            return JsonHelper::success([
                'success' => false,
                'message' => 'Parámetros erróneos',
                'data' => array_merge($request->all(), [
                    'content' => $content,
                    'contentModel' => $contentModel,
                ]),
            ]);
        }

        $metadata = [];

        $uploadFile = $request->file('file');

        if (!$content) {
            return JsonHelper::success([
                'success' => false,
                'data' => array_merge($request->all(), [
                    'content' => $content,
                    'contentModel' => $contentModel,
                ]),
            ]);
        }

        $file = File::addFile($uploadFile, 'content', false);

        if ($file) {
            $contentFile = ContentFile::firstOrCreate([
                'content_id' => $content->id,
                'file_id' => $file->id,
            ]);

            $metadata = [
                'file_id' => $file->id,
                'module' => $file->module,
                'title' => $file->title,
                'name' => $file->name,
                'alt' => $file->alt,
                'size' => $file->size,
                'extension' => $file->fileType?->extension,
            ];
        }

        return JsonHelper::accepted([
            'success' => (bool) $file,
            "file" => array_merge([
                'url' => $file?->thumbnail('normal'),
            ], $metadata),

        ]);
    }

    /**
     * Elimina un archivo adjunto o imagen relacionado con el contenido.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function ajaxRemoveFile(Request $request): JsonResponse
    {
        $fileId = $request->get('file_id');
        $file = File::find($fileId);

        if ($file && $file->user_id === auth()->id()) {
            $file->safeDelete();
        }

        return JsonHelper::accepted([
            'success' => true,
        ]);
    }

    public function ajaxPageCreate(Content $content)
    {

    }

    public function ajaxPageUpdate(Request $request, ContentPage $contentPage, string $contentType)
    {
        // TODO: Crear validación request.

        $content = $request->get('content');

        if (!$content) {
            return JsonHelper::success([
                'success' => false,
            ]);
        }

        $contentPageRawAvailable = ContentAvailablePageRaw::where('type', $contentType)->first();

        $contentPageRaw = ContentPageRaw::where('content_page_id', $contentPage->id)
            ->where('available_page_raw_id', $contentPageRawAvailable->id)
            ->first();

        if (! $contentPageRaw) {
            $contentPageRaw = ContentPageRaw::create([
                'content_page_id' => $contentPage->id,
                'available_page_raw_id' => $contentPageRawAvailable->id,
            ]);
        }

        $contentPageRaw->content = $content;
        $contentPageRaw->save();

        $html = TextFormatParseHelper::arrayToHtml($content);

        $dataUpdate = [
            'current_page_raw_id' => $contentPageRawAvailable->id,
            'title' => $request->get('title'),
            'slug' => Str::slug($request->get('slug')),
            'content' => $html,
        ];

        $contentPage->update(array_filter($dataUpdate));

        // TODO: Guardar imagen principal de la página??????

        // TODO: Metadatos secundarios: calidad del texto, derechos de autor, contar palabras totales, contar palabras clave

        return JsonHelper::accepted([
            'success' => true,
            'request' => $request->all(),
            'contentPage' => $contentPage,
        ]);

    }

    /**
     * Devuelve el contenido RAW de la página recibida.
     *
     * @param ContentPage $contentPage Página de la que solicita el contenido raw
     *
     * @return JsonResponse
     */
    public function ajaxPageGetContent(ContentPage $contentPage): JsonResponse
    {
        return response()->json(json_decode($contentPage->raw->content, true));
    }
}

