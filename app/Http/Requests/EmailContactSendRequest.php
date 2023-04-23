<?php

namespace App\Http\Requests\Dashboard\Tag;

use Illuminate\Foundation\Http\FormRequest;
use function auth;

/**
 * Request para crear un nuevo tag.
 */
class TagStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('store', \App\Models\Tag::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|max:255|unique:tags,slug',
            'description' => 'nullable|string|max:255',
        ];
    }
}
