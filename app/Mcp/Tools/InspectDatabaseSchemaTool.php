<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Server\Tool;

class InspectDatabaseSchemaTool extends Tool
{
    /**
     * @return non-empty-string
     */
    public function description(): string
    {
        return 'Inspects a specific database table and returns its columns and their data types.';
    }

    /**
     * @param  string  $tableName  The name of the table to inspect.
     */
    public function handle(string $tableName): string
    {
        if (! Schema::hasTable($tableName)) {
            return "Error: Table '{$tableName}' does not exist.";
        }

        $columns = Schema::getColumnListing($tableName);
        $schema = [];

        foreach ($columns as $column) {
            $schema[] = [
                'name' => $column,
                'type' => Schema::getColumnType($tableName, $column),
            ];
        }

        $indexes = array_map(fn ($index) => $index['name'].' ('.implode(', ', $index['columns']).')', Schema::getIndexes($tableName));
        $foreignKeys = array_map(fn ($fk) => $fk['columns'][0].' -> '.$fk['foreign_table'].'.'.$fk['foreign_columns'][0], Schema::getForeignKeys($tableName));

        return json_encode([
            'table' => $tableName,
            'columns' => $schema,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
        ], JSON_PRETTY_PRINT);
    }
}
