<?php

namespace App\Http\Requests\Dashboard\Content;

use App\Models\Content\Content;
use Illuminate\Foundation\Http\FormRequest;
use function auth;
use function trim;

/**
 * Request para actualizar tag.
 */
class ContentSeoUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('updateSeo', Content::find($this->get('content_id')));
    }

    public function prepareForValidation()
    {
        $model = Content::find($this->get('id'));

        if ($model) {
            $this->merge([
                'image_alt' => trim($this->get('image_alt')),
                'distribution' => trim($this->get('distribution')),
                'keywords' => trim($this->get('keywords')),
                'revisit_after' => trim($this->get('revisit_after')),
                'description' => trim($this->get('description')),
                'robots' => trim($this->get('robots')),
                'og_title' => trim($this->get('og_title')),
                'og_type' => trim($this->get('og_type')),
                'twitter_card' => trim($this->get('twitter_card')),
                'twitter_creator' => trim($this->get('twitter_creator')),
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
            'image_alt' => 'nullable|string|max:255',
            'distribution' => 'nullable|string|max:255',
            'keywords' => 'nullable|string|max:511',
            'revisit_after' => 'nullable|string|max:32',
            'description' => 'nullable|string|max:255',
            'robots' => 'nullable|string|max:30',
            'og_title' => 'nullable|string|max:255',
            'og_type' => 'nullable|string|max:50',
            'twitter_card' => 'nullable|string|max:50',
            'twitter_creator' => 'nullable|string|max:511',
        ];
    }
}
