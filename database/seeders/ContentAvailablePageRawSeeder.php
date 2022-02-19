<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Class ContentAvailablePageRawSeeder
 */
class ContentAvailablePageRawSeeder extends Seeder
{
    private $tableName = 'content_available_page_raw';

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = [
            [
                'name' => 'Markdown',
                'description' => 'Lenguaje de marcado ligero que trata de conseguir la máxima legibilidad y facilidad de publicación tanto en su forma de entrada como de salida, inspirándose en muchas convenciones existentes.',
                'type' => 'markdown',
                'extension' => 'md',
            ],
            [
                'name' => 'JSON',
                'description' => 'JSON es un formato de texto sencillo para el intercambio de datos. Se trata de un subconjunto de la notación literal de objetos de JavaScript, aunque, debido a su amplia adopción como alternativa a XML, se considera un formato independiente del lenguaje.',
                'type' => 'json',
                'extension' => 'json',
            ],
            [
                'name' => 'Excel Old',
                'description' => 'Microsoft Excel es una hoja de cálculo desarrollada por Microsoft para Windows, macOS, Android e iOS. Cuenta con cálculo, gráficas, tablas calculares y un lenguaje de programación macro llamado Visual Basic para aplicaciones.',
                'type' => 'excel-old',
                'extension' => 'xls',
            ],
            [
                'name' => 'Excel',
                'description' => 'Microsoft Excel es una hoja de cálculo desarrollada por Microsoft para Windows, macOS, Android e iOS. Cuenta con cálculo, gráficas, tablas calculares y un lenguaje de programación macro llamado Visual Basic para aplicaciones.',
                'type' => 'excel',
                'extension' => 'xlsx',
            ],
            [
                'name' => 'Latex',
                'description' => 'Es un sistema de composición de textos, orientado a la creación de documentos escritos que presenten una alta calidad tipográfica',
                'type' => 'latex',
                'extension' => 'tex',
            ],
            [
                'name' => 'HTML',
                'description' => 'HyperText Markup Language, hace referencia al lenguaje de marcado para la elaboración de páginas web.',
                'type' => 'html',
                'extension' => 'html',
            ],
            [
                'name' => 'Open Document ODT',
                'description' => 'Formato de Documento Abierto para Aplicaciones Ofimáticas (OpenDocument), es un formato de archivo abierto y estándar para el almacenamiento de documentos ofimáticos tales como hojas de cálculo, textos, gráficas y presentaciones',
                'type' => 'odt',
                'extension' => 'odt',
            ],
            [
                'name' => 'Microsoft Word (Doc)',
                'description' => 'Utilizada para documentos de procesamiento de texto almacenados en el formato de archivo binario Microsoft Word ',
                'type' => 'doc-old',
                'extension' => 'doc',
            ],
            [
                'name' => 'Microsoft Word (DocX',
                'description' => 'Utilizada para documentos de procesamiento de texto almacenados en el formato de archivo binario Microsoft Word ',
                'type' => 'doc',
                'extension' => 'docx',
            ],
            [
                'name' => 'Texto Plano',
                'description' => 'Documento de texto plano sin formato',
                'type' => 'text',
                'extension' => 'txt',
            ],


        ];

        $now = Carbon::now();

        foreach ($datas as $data) {
            $exist = DB::table($this->tableName)
                ->where('extension', $data['extension'])
                ->first();

            if (! $exist) {
                DB::table($this->tableName)->insert(
                    array_merge($data, [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                );
            }
        }
    }
}
