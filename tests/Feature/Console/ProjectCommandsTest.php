<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProjectCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_generate_command_executes_successfully(): void
    {
        $this->artisan('sitemap:generate')
            ->expectsOutputToContain('Sitemap generado correctamente')
            ->assertExitCode(0);

        $this->assertTrue(File::exists(public_path('sitemap.xml')));
    }

    public function test_project_clear_command_with_no_key_executes_successfully(): void
    {
        $this->artisan('project:clear', ['--no-key' => true])
            ->assertExitCode(0);
    }

    public function test_xerintel_clear_alias_executes_successfully(): void
    {
        $this->artisan('xerintel:clear', ['--no-key' => true])
            ->assertExitCode(0);
    }
}
