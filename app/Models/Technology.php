<?php

namespace App\Models;

use App\Http\Traits\ImageTrait;
use App\Models\BaseModels\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Technology extends BaseModel
{
    use ImageTrait;

    protected $table = 'technologies';

    protected $fillable = ['name', 'slug', 'description', 'color'];


    /**
     * Asocia con la imagen de la tecnología.
     *
     * @return BelongsTo
     */
    public function image(): BelongsTo
    {
        return $this->belongsTo(File::class, 'image_id', 'id');
    }

}
