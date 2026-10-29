<?php

declare(strict_types=1);

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
        $page = max(1, (int) ($request->json('page') ?? $request->input('page', 1)));
        $size = min(100, max(1, (int) ($request->json('size') ?? $request->input('size', 10))));

        $tableHeads = Language::getTableHeads($page);
        $columns = array_keys($tableHeads);

        $requestedOrderBy = (string) ($request->json('orderBy') ?? $request->input('orderBy', 'created_at'));
        $orderBy = in_array($requestedOrderBy, array_merge($columns, ['id', 'created_at']), true)
            ? $requestedOrderBy
            : 'created_at';

        $requestedDirection = strtoupper((string) ($request->json('orderDirection') ?? $request->input('orderDirection', 'DESC')));
        $orderDirection = $requestedDirection === 'ASC' ? 'ASC' : 'DESC';

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
            ],
        ]);
    }
}
