<?php

namespace App\Http\Requests\Dashboard\Content;

use App\Models\Content\Content;
use Illuminate\Foundation\Http\FormRequest;
use function auth;

/**
 * Request para crear un nuevo tag.
 */
class ContentStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('store', Content::class);
    }

    public function prepareForValidation()
    {
        //dd($this->all());

        $this->merge([
            'title' => trim($this->get('title')),
            'slug' => trim($this->get('slug')),
            'excerpt' => trim($this->get('excerpt')),
            'author_id' => (int) trim($this->get('author_id')),
            'platform_id' => (int) trim($this->get('platform_id')),
            'type_id' => (int) trim($this->get('type_id')),

            //'image_id'

            'is_active' => (bool) $this->get('is_active'),
            'is_featured' => (bool) $this->get('is_featured'),
            'is_comment_enabled' => (bool) $this->get('is_comment_enabled'),
            'is_comment_anonymous' => (bool) $this->get('is_comment_anonymous'),
            'is_visible_on_archive' => (bool) $this->get('is_visible_on_archive'),
            //'programated_at' => trim($this->get('programated_at')),

            'is_visible_on_home' => (bool) $this->get('is_visible_on_home'),
            'is_visible_on_menu' => (bool) $this->get('is_visible_on_menu'),
            'is_visible_on_footer' => (bool) $this->get('is_visible_on_footer'),
            'is_visible_on_sidebar' => (bool) $this->get('is_visible_on_sidebar'),
            'is_visible_on_search' => (bool) $this->get('is_visible_on_search'),
            'is_visible_on_rss' => (bool) $this->get('is_visible_on_rss'),
            'is_visible_on_sitemap' => (bool) $this->get('is_visible_on_sitemap'),
            'is_visible_on_sitemap_news' => (bool) $this->get('is_visible_on_sitemap_news'),

        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string|min:5|max:255',
            'slug' => 'required|min:3|max:255|unique:contents,slug',
            'excerpt' => 'nullable|string|max:255',
            'author_id' => 'required|exists:users,id',
            'platform_id' => 'required|exists:platforms,id',
            'type_id' => 'required|exists:content_available_types,id',
            //'image_id' => 'nullable|exists:images,id', // TODO: Implementarvalidación
            //'is_copyright_valid' => 'required|boolean', //Esto se comprueba
            // en el controlador llamando a método en el modelo
            'is_active' => 'required|boolean',
            'is_featured' => 'required|boolean',
            'is_comment_enabled' => 'required|boolean',
            'is_comment_anonymous' => 'required|boolean',
            'is_visible_on_archive' => 'required|boolean',
            //'processed_at' => 'nullable|date', // Se comprueba en el controlador
            //'published_at' => 'nullable|date', // Se comprueba en el controlador
            'programated_at' => 'nullable|date',

            'is_visible_on_home' => 'required|boolean',
            'is_visible_on_menu' => 'required|boolean',
            'is_visible_on_footer' => 'required|boolean',
            'is_visible_on_sidebar' => 'required|boolean',
            'is_visible_on_search' => 'required|boolean',
            'is_visible_on_rss' => 'required|boolean',
            'is_visible_on_sitemap' => 'required|boolean',
            'is_visible_on_sitemap_news' => 'required|boolean',


            // Relaciones
            'contributors.*' => 'nullable|exists:users,id',
            'contents_related.*' => 'nullable|exists:contents,id',
            'tags.*' => 'nullable|exists:tags,id',
            'categories.*' => 'nullable|exists:categories,id',

        ];
    }
}
