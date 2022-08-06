<?php

namespace App\Http\Requests\Dashboard\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use function auth;

/**
 * Request para eliminar una categoría.
 */
class CategoryDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('delete', Category::find($this->get('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required|exists:categories,id',
        ];
    }
}
