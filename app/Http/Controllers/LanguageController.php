<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\Request;
use function array_keys;
use function asset;
use function response;

/**
 * Class LanguageController
 */
class LanguageController extends Controller
{

    /***************** AJAX *****************/



    public function ajaxGetLanguages(Request $request)
    {
        $page = $request->json('page') ?? 1;
        $size = $request->json('size') ?? 10;
        $orderBy = $request->json('orderBy') ?? 'created_at';
        $orderDirection = $request->json('orderDirection') ?? 'DESC';

        $tableHeads = Language::getTableHeads($page);
        $columns = array_keys($tableHeads);
        $tableRows = Language::getTableRowsByPage($size, $page, $columns, $orderBy, $orderDirection);
        $totalElements = Language::count();

        $cellsInfo = Language::getTableCellsInfo();

        foreach ($tableRows as $tableRow) {
            $tableRow->icon64 = asset($tableRow->icon64);
        }

        return response()->json([
            'data' => [
                'heads' => $tableHeads,
                'rows' => $tableRows,
                'currentPage' => $page,
                'totalElements' => $totalElements,
                'size' => $size,
                'cellsInfo' => $cellsInfo,
            ]
        ]);
    }

}
