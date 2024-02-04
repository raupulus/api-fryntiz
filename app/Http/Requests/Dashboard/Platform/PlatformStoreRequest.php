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
            'domain' => trim($this->get('domain')),
            'url_about' => trim($this->get('url_about')),
            'youtube_channel_id' => trim($this->get('youtube_channel_id')),
            'youtube_presentation_video_id' => trim($this->get('youtube_presentation_video_id')),
            'twitter' => trim($this->get('twitter')),
            'twitter_token' => trim($this->get('twitter_token')),
            'mastodon' => trim($this->get('mastodon')),
            'mastodon_token' => trim($this->get('mastodon_token')),
            'twitch' => trim($this->get('twitch')),
            'tiktok' => trim($this->get('tiktok')),
            'instagram' => trim($this->get('instagram')),
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
            'title' => 'required|string|max:511',
            'slug' => 'required|max:255|unique:platforms,slug',
            'description' => 'nullable|string|max:1023',
            'domain' => 'nullable|string|max:255',
            'url_about' => 'nullable|string|max:255',
            'youtube_channel_id' => 'nullable|string|max:64',
            'youtube_presentation_video_id' => 'nullable|string|max:64',
            'twitter' => 'nullable|string|max:255',
            'twitter_token' => 'nullable|string|max:511',
            'mastodon' => 'nullable|string|max:255',
            'mastodon_token' => 'nullable|string|max:511',
            'twitch' => 'nullable|string|max:255',
            'tiktok' => 'nullable|string|max:255',
            'instagram' => 'nullable|string|max:255',
        ];
    }
}
