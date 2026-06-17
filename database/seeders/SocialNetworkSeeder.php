<?php

namespace Database\Seeders;

use App\Models\SocialNetwork;
use Illuminate\Database\Seeder;

/**
 * Seeder de redes sociales disponibles.
 */
class SocialNetworkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $networks = [
            [
                'name' => 'Facebook',
                'slug' => 'facebook',
                'type' => 'social',
                'color' => '#1877F2',
                'url' => 'https://www.facebook.com',
                'url_user' => 'https://www.facebook.com/',
                'icon' => 'fab fa-facebook',
            ],
            [
                'name' => 'Instagram',
                'slug' => 'instagram',
                'type' => 'social',
                'color' => '#E4405F',
                'url' => 'https://www.instagram.com',
                'url_user' => 'https://www.instagram.com/',
                'icon' => 'fab fa-instagram',
            ],
            [
                'name' => 'Twitter / X',
                'slug' => 'twitter',
                'type' => 'social',
                'color' => '#000000',
                'url' => 'https://x.com',
                'url_user' => 'https://x.com/',
                'icon' => 'fab fa-x-twitter',
            ],
            [
                'name' => 'Mastodon',
                'slug' => 'mastodon',
                'type' => 'social',
                'color' => '#6364FF',
                'url' => 'https://mastodon.social',
                'url_user' => 'https://mastodon.social/@',
                'icon' => 'fab fa-mastodon',
            ],
            [
                'name' => 'YouTube',
                'slug' => 'youtube',
                'type' => 'video',
                'color' => '#FF0000',
                'url' => 'https://www.youtube.com',
                'url_user' => 'https://www.youtube.com/@',
                'icon' => 'fab fa-youtube',
            ],
            [
                'name' => 'BlueSky',
                'slug' => 'bluesky',
                'type' => 'social',
                'color' => '#0085FF',
                'url' => 'https://bsky.app',
                'url_user' => 'https://bsky.app/profile/',
                'icon' => 'fab fa-bluesky',
            ],
            [
                'name' => 'TikTok',
                'slug' => 'tiktok',
                'type' => 'video',
                'color' => '#000000',
                'url' => 'https://www.tiktok.com',
                'url_user' => 'https://www.tiktok.com/@',
                'icon' => 'fab fa-tiktok',
            ],
            [
                'name' => 'LinkedIn',
                'slug' => 'linkedin',
                'type' => 'professional',
                'color' => '#0A66C2',
                'url' => 'https://www.linkedin.com',
                'url_user' => 'https://www.linkedin.com/in/',
                'icon' => 'fab fa-linkedin',
            ],
            [
                'name' => 'GitHub',
                'slug' => 'github',
                'type' => 'development',
                'color' => '#181717',
                'url' => 'https://github.com',
                'url_user' => 'https://github.com/',
                'icon' => 'fab fa-github',
            ],
            [
                'name' => 'GitLab',
                'slug' => 'gitlab',
                'type' => 'development',
                'color' => '#FC6D26',
                'url' => 'https://gitlab.com',
                'url_user' => 'https://gitlab.com/',
                'icon' => 'fab fa-gitlab',
            ],
            [
                'name' => 'Twitch',
                'slug' => 'twitch',
                'type' => 'streaming',
                'color' => '#9146FF',
                'url' => 'https://www.twitch.tv',
                'url_user' => 'https://www.twitch.tv/',
                'icon' => 'fab fa-twitch',
            ],
            [
                'name' => 'Discord',
                'slug' => 'discord',
                'type' => 'messaging',
                'color' => '#5865F2',
                'url' => 'https://discord.com',
                'url_user' => null,
                'icon' => 'fab fa-discord',
            ],
            [
                'name' => 'Telegram',
                'slug' => 'telegram',
                'type' => 'messaging',
                'color' => '#26A5E4',
                'url' => 'https://telegram.org',
                'url_user' => 'https://t.me/',
                'icon' => 'fab fa-telegram',
            ],
            [
                'name' => 'Reddit',
                'slug' => 'reddit',
                'type' => 'social',
                'color' => '#FF4500',
                'url' => 'https://www.reddit.com',
                'url_user' => 'https://www.reddit.com/user/',
                'icon' => 'fab fa-reddit',
            ],
            [
                'name' => 'Pinterest',
                'slug' => 'pinterest',
                'type' => 'social',
                'color' => '#BD081C',
                'url' => 'https://www.pinterest.com',
                'url_user' => 'https://www.pinterest.com/',
                'icon' => 'fab fa-pinterest',
            ],
            [
                'name' => 'Threads',
                'slug' => 'threads',
                'type' => 'social',
                'color' => '#000000',
                'url' => 'https://www.threads.net',
                'url_user' => 'https://www.threads.net/@',
                'icon' => 'fab fa-threads',
            ],
            [
                'name' => 'Stack Overflow',
                'slug' => 'stackoverflow',
                'type' => 'development',
                'color' => '#F58025',
                'url' => 'https://stackoverflow.com',
                'url_user' => 'https://stackoverflow.com/users/',
                'icon' => 'fab fa-stack-overflow',
            ],
            [
                'name' => 'WhatsApp',
                'slug' => 'whatsapp',
                'type' => 'messaging',
                'color' => '#25D366',
                'url' => 'https://www.whatsapp.com',
                'url_user' => null,
                'icon' => 'fab fa-whatsapp',
            ],
            [
                'name' => 'Nostr',
                'slug' => 'nostr',
                'type' => 'social',
                'color' => '#8B5CF6',
                'url' => 'https://nostr.com',
                'url_user' => null,
                'icon' => null,
            ],
            [
                'name' => 'Codeberg',
                'slug' => 'codeberg',
                'type' => 'development',
                'color' => '#2185D0',
                'url' => 'https://codeberg.org',
                'url_user' => 'https://codeberg.org/',
                'icon' => null,
            ],
        ];

        foreach ($networks as $network) {
            SocialNetwork::firstOrCreate(
                ['slug' => $network['slug']],
                $network
            );
        }
    }
}
