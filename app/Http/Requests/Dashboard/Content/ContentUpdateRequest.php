<?php

namespace App\Http\Requests\Dashboard\Content;

use App\Models\Content\Content;
use Illuminate\Foundation\Http\FormRequest;
use function auth;
use function trim;

/**
 * Request para actualizar tag.
 */
class ContentUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('update', Content::find($this->get('id')));
    }

    public function prepareForValidation()
    {
        $model = Content::find($this->get('id'));

        if ($model) {
            $this->merge([
                'title' => trim($this->get('title')) ?? $model->name,
                'slug' => trim($this->get('slug')) ?? $model->slug,
                'excerpt' => trim($this->get('excerpt')),
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
            'title' => 'required|string|max:255',
            'slug' => 'required|max:255|unique:tags,slug,' . $this->get('id'),
            'excerpt' => 'nullable|string|max:255',
        ];
    }
}
