<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Content\Content;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $content_id
 * @property string $date
 * @property int $views
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Content $content
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentDailyView newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentDailyView newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentDailyView query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentDailyView whereContentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentDailyView whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentDailyView whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentDailyView whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentDailyView whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ContentDailyView whereViews($value)
 *
 * @mixin \Eloquent
 */
class ContentDailyView extends Model
{
    protected $fillable = ['content_id', 'date', 'views'];

    protected $casts = [
        'date' => 'datetime',
    ];

    /**
     * Contenido al que pertenecen las vistas.
     */
    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class, 'content_id', 'id');
    }

    /**
     * Devuelve el contenido más visto en un periodo de tiempo
     *
     * @param  Carbon  $startDate  Fecha de inicio
     */
    public static function getMostViewedInPeriod(Carbon $startDate, Carbon $endDate, ?int $limit = 10): Collection
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
     * @param  int  $contentId  El ID del contenido a buscar
     * @param  Carbon|null  $startDate  Fecha de inicio
     * @param  Carbon|null  $endDate  Fecha de fin
     */
    public static function getViewsForContent(int $contentId, ?Carbon $startDate = null, ?Carbon $endDate = null): int
    {
        $query = self::where('content_id', $contentId);

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }

        return $query->sum('views');
    }
}
