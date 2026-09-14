<?php

declare(strict_types=1);

namespace App\Models\WeatherStation\AEMET;

use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * @property int $id
 * @property string $station_name Nombre de la estación
 * @property string $station_code Indicativo climatológico de la estación
 * @property int $ozone_value Dato medio diario del contenido total de ozono, en Unidades Dobson
 * @property string $measured_on Fecha del dato (Y-m-d)
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzoneTotal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzoneTotal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AEMETOzoneTotal query()
 *
 * @mixin \Eloquent
 */
class AEMETOzoneTotal extends BaseModel
{
    use HasFactory;

    protected $table = 'meteorology_aemet_ozone_total';

    protected $fillable = [
        'station_name',
        'station_code',
        'ozone_value',
        'measured_on',
    ];

    protected $casts = [
        'measured_on' => 'date',
    ];

    public static function validation(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, [
            'station_name' => 'required|string|max:255',
            'station_code' => 'required|string|max:32',
            'ozone_value' => 'required|integer',
            'measured_on' => 'required|date',
        ]);
    }

    public static function isValid(array $data): bool
    {
        return ! self::validation($data)->fails();
    }

    /**
     * Recibe las filas ya parseadas del CSV (una por estación) y las guarda.
     *
     * Una fila por estación y día: una nueva petición el mismo día actualiza,
     * no duplica.
     *
     * @param  array<int,array<string,mixed>>  $apiResponseArray
     * @return array<int,self>
     */
    public static function saveFromApi(array $apiResponseArray): array
    {
        $result = [];

        foreach ($apiResponseArray as $row) {
            if (! self::isValid($row)) {
                continue;
            }

            $result[] = self::updateOrCreate(
                [
                    'station_code' => $row['station_code'],
                    'measured_on' => $row['measured_on'],
                ],
                self::validation($row)->validated(),
            );
        }

        return $result;
    }
}
