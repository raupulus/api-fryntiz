<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Category;
use App\Models\Content\Content;
use App\Models\Content\ContentPage;
use App\Models\SocialNetwork;
use Database\Factories\PageFactory;
use Database\Factories\PostCategoryFactory;
use Database\Factories\PostFactory;
use Database\Factories\SocialMediaFactory;
use Database\Seeders\ContentAvailableStatusSeeder;
use Database\Seeders\ContentAvailableTypesSeeder;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FactoriesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        (new RolesTableSeeder)->run();
        (new ContentAvailableStatusSeeder)->run();
        (new ContentAvailableTypesSeeder)->run();
    }

    public function test_post_factory_creates_published_post(): void
    {
        $post = PostFactory::new()->published()->create();

        $this->assertInstanceOf(Content::class, $post);
        $this->assertTrue($post->is_active);
        $this->assertNotNull($post->published_at);
        $this->assertNotNull($post->slug);
    }

    public function test_post_factory_creates_draft_and_featured_post(): void
    {
        $post = PostFactory::new()->draft()->featured()->create();

        $this->assertInstanceOf(Content::class, $post);
        $this->assertFalse($post->is_active);
        $this->assertTrue($post->is_featured);
        $this->assertNull($post->published_at);
    }

    public function test_page_factory_creates_active_page(): void
    {
        $page = PageFactory::new()->active()->create();

        $this->assertInstanceOf(ContentPage::class, $page);
        $this->assertNotNull($page->title);
        $this->assertNotNull($page->slug);
        $this->assertNotNull($page->content);
        $this->assertIsArray(json_decode((string) $page->content, true));
    }

    public function test_post_category_factory_creates_category(): void
    {
        $category = PostCategoryFactory::new()->create();

        $this->assertInstanceOf(Category::class, $category);
        $this->assertNotNull($category->name);
        $this->assertNotNull($category->slug);
    }

    public function test_social_media_factory_creates_active_social_network(): void
    {
        $social = SocialMediaFactory::new()->active()->create();

        $this->assertInstanceOf(SocialNetwork::class, $social);
        $this->assertNotNull($social->name);
        $this->assertNotNull($social->slug);
    }
}
