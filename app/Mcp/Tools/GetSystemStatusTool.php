<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Get basic status, environment, and database connection details of the Laravel application.')]
class GetSystemStatusTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): ResponseFactory
    {
        $dbConnected = false;
        $dbError = null;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Exception $e) {
            $dbError = $e->getMessage();
        }

        $status = [
            'app_name' => config('app.name'),
            'env' => config('app.env'),
            'debug' => config('app.debug'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database' => [
                'connected' => $dbConnected,
                'driver' => config('database.default'),
                'error' => $dbError,
            ],
        ];

        return Response::structured($status);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            // No arguments required
        ];
    }
}
