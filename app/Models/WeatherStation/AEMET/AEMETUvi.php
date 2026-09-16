<?php

declare(strict_types=1);

namespace App\Models\WeatherStation\AEMET;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * @property int $id
 * @property int $uv_index Índice UV máximo previsto en condiciones de cielo despejado
 * @property string $valid_date Fecha para la que es válida la predicción (Y-m-d)
 * @property Carbon $elaborated_at Momento de elaboración del boletín
 * @property Carbon $modified_at Última modificación del boletín según AEMET
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static> newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static> query()
 *
 * @mixin \Eloquent
 */
class AEMETUvi extends BaseModel
{
    use HasFactory;

    protected $table = 'meteorology_aemet_uvi';

    protected $fillable = [
        'uv_index',
        'valid_date',
        'elaborated_at',
        'modified_at',
    ];

    protected $casts = [
        'valid_date' => 'date',
        'elaborated_at' => 'datetime',
        'modified_at' => 'datetime',
    ];

    public static function validation(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'uv_index' => 'required|integer|min:0|max:20',
            'valid_date' => 'required|date',
            'elaborated_at' => 'required|date',
            'modified_at' => 'required|date',
        ]);
    }

    public static function isValid(array $data): bool
    {
        return ! self::validation($data)->fails();
    }

    /**
     * Una fila por día de validez: una nueva petición para el mismo día
     * actualiza, no duplica.
     */
    public static function saveFromApi(array $data): ?self
    {
        if (! self::isValid($data)) {
            return null;
        }

        return self::updateOrCreate(
            ['valid_date' => $data['valid_date']],
            self::validation($data)->validated(),
        );
    }
}
