<?php

namespace App\Models\CV;

use App\Models\File;
use Illuminate\Database\Eloquent\Model;
use function array_key_exists;

/**
 * Class Curriculum
 */
class Curriculum extends Model
{
    protected $table = 'cv';

    protected $guarded = [
        'id'
    ];

    /**
     * Relación con todos los proyectos asociados al curriculum.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function projects()
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Relación con la imagen asociada al curriculum.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function image()
    {
        return $this->hasOne(File::class, 'id', 'image_id');
    }

    /**
     * Devuelve la ruta hacia la foto asociada al curriculum.
     *
     * @return string
     */
    public function getUrlImageAttribute()
    {
        return $this->image ? $this->image->url : File::urlDefaultImage('large');
    }


    // BORRAR desde aquí (refactorizando antes)

    public function getUrlImageThumbnailMicroAttribute()
    {
        return $this->urlThumbnail('micro');
    }

    public function getUrlImageThumbnailSmallAttribute()
    {
        return $this->urlThumbnail('small');
    }

    public function getUrlImageThumbnailMediumAttribute()
    {
        return $this->urlThumbnail('medium');
    }

    public function getUrlImageThumbnailNormalAttribute()
    {
        return $this->urlThumbnail('normal');
    }

    public function getUrlImageThumbnailLargeAttribute()
    {
        return $this->urlThumbnail('large');
    }

    // BORRAR Hasta aquí

    /**
     * Devuelve el thumbnail de la imagen asociada.
     *
     * @param $size
     *
     * @return mixed
     */
    public function urlThumbnail($size = 'medium')
    {
        if ($this->image) {
            return $this->image->thumbnail($size);
        }

        return File::urlDefaultImage($size);
    }


    /**
     * Elimina de forma segura un curriculum y los datos asociados.
     *
     * @return bool
     */
    public function safeDelete()
    {
        ## Elimino la imagen asociada al curriculum y todas las miniaturas.
        if ($this->image) {
            $this->image->safeDelete();
        }

        return $this->delete();
    }

    /**
     * Devuelve un array con todos los títulos de una tabla.
     *
     * @return array
     */
    public static function getTableHeads()
    {
        return [
            'Título' => 'title',
            'Descargable' => 'is_downloadable',
            'Fecha de creación' => 'created_at',
        ];
    }

    /**
     * Devuelve un array con información sobre los atributos de la tabla.
     *
     * @return \string[][]
     */
    public static function getTableCellsInfo()
    {
        return [
            'title' => [
                'type' => 'image',
            ],
            'is_downloadable' => [
                'type' => 'boolean',
                'wrapper' => 'span',
                'class' => 'switch-on-off',
            ],
            'created_at' => [
                'type' => 'timestamp',
                'format' => 'd/m/Y',
            ],
        ];
    }

    /**
     * Devuelve los resultados para una página.
     *
     * @param number $size Tamaño de cada página
     * @param number $page Página a la que buscar.
     *
     * @return array
     */
    public static function getTableRowsByPage($size, $page, $columns,
                                              $orderBy, $orderDirection = 'ASC')
    {
        return self::select($columns)
            ->offset(($page * $size) - $size)
            ->limit($size)
            ->orderBy($orderBy, $orderDirection)
            ->get();
    }

    /**
     * Devuelve un array con todos los atributos para un modelo instanciado
     *
     * @return array
     */
    public function getAllAttributes()
    {
        $columns = $this->getFillable();
        // Another option is to get all columns for the table like so:
        // $columns = \Schema::getColumnListing($this->table);
        // but it's safer to just get the fillable fields

        $attributes = $this->getAttributes();

        foreach ($columns as $column) {
            if (!array_key_exists($column, $attributes)) {
                $attributes[$column] = null;
            }
        }
        return $attributes;
    }
}
