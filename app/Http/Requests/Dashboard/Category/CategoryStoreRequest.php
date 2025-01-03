<?php

namespace App\Http\Requests\Dashboard\Category;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Foundation\Http\FormRequest;
use function auth;

/**
 * Request para crear una nueva categoría.
 */
class CategoryStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('store', Category::class);
    }

    public function prepareForValidation()
    {
        $this->merge([
            'name' => trim($this->get('name')),
            'slug' => Str::slug($this->get('slug') ?? $this->get('name')),
            'description' => trim($this->get('description')),
            'color' => trim($this->get('color')),
            'priority' => $this->get('priority') ?? ($this->get('parent_id') ? 2 : 1),
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
            'parent_id' => 'nullable|exists:categories,id',
            'name' => 'required|string|max:255|unique:categories,name',
            'slug' => 'required|max:255|unique:categories,slug',
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'priority' => 'nullable|integer',
        ];
    }
}
