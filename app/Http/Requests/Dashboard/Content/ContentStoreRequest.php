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

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|max:255|unique:tags,slug',
            'excerpt' => 'nullable|string|max:255',
        ];
    }
}
