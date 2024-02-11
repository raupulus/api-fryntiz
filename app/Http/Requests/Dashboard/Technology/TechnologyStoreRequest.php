<?php

namespace App\Http\Requests\Dashboard\Technology;

use App\Models\Technology;
use Illuminate\Foundation\Http\FormRequest;
use function auth;

/**
 * Request para crear una nueva categoría.
 */
class TechnologyStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('store', Technology::class);
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
            'slug' => 'required|max:255|unique:technologies,slug',
            'description' => 'nullable|string|max:512',
            'color' => 'nullable|string|max:255',
        ];
    }
}
