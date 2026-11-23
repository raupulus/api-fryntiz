<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

#[Description('Gets structural information about an Eloquent model (fillable fields, relationships, scopes) using Reflection.')]
class GetModelInfoTool extends Tool
{
    /**
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'modelClass' => $schema->string()
                ->description('Fully qualified class name of the model (e.g. App\\Models\\User).')
                ->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $modelClass = ltrim((string) $request->string('modelClass'), '\\');

        if (! str_starts_with($modelClass, 'App\\Models\\')) {
            return Response::error("Error: Class '{$modelClass}' must be under the App\\Models\\ namespace.");
        }

        if (! class_exists($modelClass)) {
            return Response::error("Error: Class '{$modelClass}' does not exist.");
        }

        if (! is_subclass_of($modelClass, Model::class)) {
            return Response::error("Error: Class '{$modelClass}' is not an Eloquent model.");
        }

        try {
            $reflection = new ReflectionClass($modelClass);

            if ($reflection->isAbstract()) {
                return Response::error("Error: Class '{$modelClass}' is abstract and cannot be instantiated.");
            }

            $model = new $modelClass;

            $fillable = $model->getFillable();
            $table = $model->getTable();

            $scopes = [];
            $relations = [];

            foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if (str_starts_with($method->getName(), 'scope') && $method->getName() !== 'scope') {
                    $scopes[] = $method->getName();
                }

                $returnType = $method->getReturnType();
                if ($returnType instanceof ReflectionNamedType
                    && str_contains($returnType->getName(), 'Illuminate\\Database\\Eloquent\\Relations\\')) {
                    $relations[] = [
                        'method' => $method->getName(),
                        'type' => class_basename($returnType->getName()),
                    ];
                }
            }

            return Response::structured([
                'class' => $modelClass,
                'table' => $table,
                'fillable' => $fillable,
                'scopes' => $scopes,
                'relations' => $relations,
            ]);
        } catch (\Throwable $e) {
            return Response::error('Error analyzing model: '.$e->getMessage());
        }
    }
}
