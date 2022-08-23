<?php

namespace App\Http\Requests\Dashboard\Platform;

use App\Models\Platform;
use Illuminate\Foundation\Http\FormRequest;
use function auth;

/**
 * Request para eliminar una plataforma.
 */
class PlatformDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('delete', Platform::find($this->get('id')));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'id' => 'required|exists:platforms,id',
        ];
    }
}
