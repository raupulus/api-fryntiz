<?php

namespace App\Http\Requests\Cv;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class StoreCvCollaborationRequest
 */
class StoreCvCollaborationRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'title' => Str::of($this->name)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'description' => Str::of($this->description)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'url' => Str::of($this->url)->trim()->replaceMatches('/\s+/','')
                ->__toString(),
            'urlinfo' => Str::of($this->url)->trim()->replaceMatches('/\s+/','')
                ->__toString(),
            'repository' => Str::of($this->url)->trim()->replaceMatches('/\s+/','')
                ->__toString(),
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
            'title' => 'required|string|min:3|max:511',
            'description' => 'nullable|string|min:10|max:1500',
            'url' => 'nullable|string|min:12|max:511|url',
            'urlinfo' => 'nullable|string|min:12|max:511|url',
            'repository' => 'nullable|string|min:12|max:511|url',
        ];
    }
}
