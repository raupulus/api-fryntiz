<?php

namespace App\Http\Requests\Dashboard\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use function auth;
use function trim;

/**
 * Request para crear una nueva categoría.
 */
class CategoryUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('update', Category::find($this->get('id')));
    }

    public function prepareForValidation()
    {
        $model = Category::find($this->get('id'));

        if ($model) {
            $this->merge([
                'name' => trim($this->get('name')) ?? $model->name,
                'slug' => trim($this->get('slug')) ?? $model->slug,
                'description' => trim($this->get('description')),
            ]);
        }
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
            'slug' => 'required|max:255|unique:category,slug',
            'description' => 'nullable|string|max:255',
        ];
    }
}
