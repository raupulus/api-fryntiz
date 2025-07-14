<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContentDailyView extends Model
{
    protected $fillable = ['content_id', 'date', 'views'];

    protected $dates = ['date'];

    /**
     * Devuelve el contenido más visto en un periodo de tiempo
     *
     * @param Carbon $startDate Fecha de inicio
     * @param Carbon $endDate
     * @param int|null $limit
     * @return Collection
     */
    public static function getMostViewedInPeriod(Carbon $startDate, Carbon $endDate, int|null $limit = 10): Collection
    {
        return self::select('content_id', DB::raw('SUM(views) as total_views'))
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('content_id')
            ->orderBy('total_views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Devuelve el contenido más visto en un periodo de tiempo
     *
     * @param int $contentId El ID del contenido a buscar
     * @param Carbon|null $startDate Fecha de inicio
     * @param Carbon|null $endDate Fecha de fin
     * @return int
     */
    public static function getViewsForContent(int $contentId, Carbon|null $startDate = null, Carbon|null $endDate = null): int
    {
        $query = self::where('content_id', $contentId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        return $query->sum('views');
    }
}
