<?php

namespace App\Http\Requests\Dashboard\Technology;

use App\Models\Technology;
use Illuminate\Foundation\Http\FormRequest;
use function auth;
use function trim;

/**
 * Request para crear una nueva categoría.
 */
class TechnologyUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('update', Technology::find($this->get('id')));
    }

    public function prepareForValidation()
    {
        $model = Technology::find($this->get('id'));

        if ($model) {
            $this->merge([
                'name' => trim($this->get('name')) ?? $model->name,
                'slug' => trim($this->get('slug')) ?? $model->slug,
                'description' => trim($this->get('description')),
                'color' => trim($this->get('color')),
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
            'slug' => 'required|max:255|unique:technologies,slug,' . $this->get('id'),
            'description' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
        ];
    }
}
