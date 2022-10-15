<?php

namespace App\Http\Requests\Cv;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * Class StoreCvAcademicTrainingRequest
 */
class StoreCvAcademicTrainingRequest extends FormRequest
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
            'title' => Str::of($this->name)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'entity' => Str::of($this->name)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'credential_id' => Str::of($this->name)->trim()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'learned' => Str::of($this->name)->trim()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'instructor' => Str::of($this->name)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'description' => Str::of($this->description)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'note' => Str::of($this->description)->trim()->ucfirst()
                ->replaceMatches('/\s\s+/', '')->__toString(),
            'url' => Str::of($this->url)->trim()->replaceMatches('/\s+/','')
                ->__toString(),
            'credential_url' => Str::of($this->url)->trim()->replaceMatches('/\s+/','')
                ->__toString(),
            'hours' => $this->hours ? (int) $this->hours : null,
            'expires' => $this->expires ? (bool) $this->expires : false,
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
            'entity' => 'nullable|string|min:3|max:511',
            'credential_id' => 'nullable|string|min:3|max:511',
            'url' => 'nullable|string|min:12|max:511|url',
            'credential_url' => 'nullable|string|min:12|max:511|url',
            'learned' => 'nullable|string|min:3|max:511',
            'note' => 'nullable|string|min:12|max:1500',
            'hours' => 'nullable|number|min:1|max:5000',
            'instructor' => 'nullable|string|min:12|max:511',
            'expires' => 'nullable|boolean',
            'expires_at' => 'nullable|date|before_or_equal:today',
            'expedition_at' => 'nullable|date|before_or_equal:today',
            'start_at' => 'nullable|date|before_or_equal:today',
            'end_at' => 'nullable|date|after_or_equal:start_at',
            'description' => 'nullable|string|min:10|max:1500',
        ];
    }
}
