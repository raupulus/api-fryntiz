<?php

namespace App\Http\Requests\Dashboard\Platform;

use App\Models\Platform;
use Illuminate\Foundation\Http\FormRequest;
use function auth;

/**
 * Request para crear una nueva plataforma.
 */
class PlatformStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('store', Platform::class);
    }

    public function prepareForValidation()
    {
        $this->merge([
            'user_id' => auth()->id(),
            'title' => trim($this->get('title')),
            'slug' => trim($this->get('slug')),
            'description' => trim($this->get('description')),
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
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'slug' => 'required|max:255|unique:tags,slug',
            'description' => 'nullable|string|max:255',
        ];
    }
}
