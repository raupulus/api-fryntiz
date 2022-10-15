<?php

namespace App\Http\Requests\Dashboard\Tag;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;
use function auth;

/**
 * Request para eliminar un tag.
 */
class TagDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('delete', Tag::find($this->get('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required|exists:tags,id',
        ];
    }
}
