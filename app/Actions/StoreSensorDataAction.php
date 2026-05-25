<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Model;

class StoreSensorDataAction
{
    public function execute(string $modelClass, array $data): Model
    {
        return $modelClass::create($data);
    }

    public function executeBatch(string $modelClass, array $records): array
    {
        $stored = [];
        foreach ($records as $record) {
            $stored[] = $modelClass::create($record);
        }
        return $stored;
    }
}
