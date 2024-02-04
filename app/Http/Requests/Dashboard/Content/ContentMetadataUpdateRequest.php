<?php

namespace App\Http\Requests\Dashboard\Content;

use App\Models\Content\Content;
use Illuminate\Foundation\Http\FormRequest;
use function auth;
use function trim;

/**
 * Request para actualizar tag.
 */
class ContentMetadataUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->id() && auth()->user()->can('updateMetadata', Content::find($this->get('content_id')));
    }

    public function prepareForValidation()
    {
        $model = Content::find($this->get('id'));

        if ($model) {
            $this->merge([
                'web' => trim($this->get('web')),
                'telegram_channel' => trim($this->get('telegram_channel')),
                'youtube_channel' => trim($this->get('youtube_channel')),
                'youtube_video' => trim($this->get('youtube_video')),
                'youtube_video_id' => trim($this->get('youtube_video_id')),
                'gitlab' => trim($this->get('gitlab')),
                'github' => trim($this->get('github')),
                'mastodon' => trim($this->get('mastodon')),
                'twitter' => trim($this->get('twitter')),
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
            'web' => 'nullable|string|max:255',
            'telegram_channel' => 'nullable|string|max:255',
            'youtube_channel' => 'nullable|string|max:255',
            'youtube_video' => 'nullable|string|max:255',
            'youtube_video_id' => 'nullable|string|max:255',
            'gitlab' => 'nullable|string|max:255',
            'github' => 'nullable|string|max:255',
            'mastodon' => 'nullable|string|max:255',
            'twitter' => 'nullable|string|max:255',
        ];
    }
}
