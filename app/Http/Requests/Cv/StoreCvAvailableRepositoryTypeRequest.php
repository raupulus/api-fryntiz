<?php

namespace App\Http\Requests\Cv;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class StoreCvAvailableRepositoryTypeRequest
 */
class StoreCvAvailableRepositoryTypeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'slug' => Str::slug($this->slug),
            //'url' => ,  // TODO → Parse protocol?? http/https
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
            'image' => 'nullable|image|max:2048',
            'title' => 'required|string|max:511',
            'name' => 'required|string|max:511',
            'slug' => 'required|string|max:511|unique:cv_available_repository_types,slug,'.$this->id,
            'url' => 'required|string|max:511|url',
        ];
    }
}
