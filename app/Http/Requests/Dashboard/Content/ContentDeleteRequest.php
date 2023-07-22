<?php

namespace App\Http\Requests\Dashboard\Content;

use App\Models\Content\Content;
use Illuminate\Foundation\Http\FormRequest;
use function auth;

/**
 * Request para eliminar un tag.
 */
class ContentDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('delete', Content::find($this->get('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required|exists:contents,id',
        ];
    }
}
