<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Printers\V2;

use App\Enums\PrintJobStatusEnum;
use App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesHardwareDeviceInfo;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Printers\V2\PrinterHeartbeatRequest;
use App\Http\Resources\V2\Printers\PrinterResource;
use App\Models\Printer;
use App\Models\User;
use App\Services\Hardware\HardwareService;
use App\Services\Printers\PrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Controlador API V2 para periféricos de impresión física.
 *
 * @group Impresoras
 */
class PrinterController extends BaseApiController
{
    use HandlesHardwareDeviceInfo;

    public function __construct(
        protected PrinterService $printerService
    ) {}

    /**
     * Listar impresoras accesibles.
     *
     * Devuelve una colección paginada de impresoras filtrables por estado y tipo.
     */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $userId = $user->isAdmin() ? null : (int) $user->id;
        $filters = $request->only(['status', 'printer_type', 'is_active']);

        $paginator = $this->printerService->getPrinters($userId, $filters);

        return $this->paginatedResponse($paginator, PrinterResource::class, 'Impresoras obtenidas exitosamente');
    }

    /**
     * Ficha de una impresora física.
     *
     * Devuelve la configuración, formatos soportados y contador de trabajos pendientes.
     */
    public function show(Printer $printer): JsonResponse
    {
        Gate::authorize('view', $printer);

        $printer->loadCount([
            'printStack as pending_jobs_count' => fn ($q) => $q->where('status', PrintJobStatusEnum::Pending),
        ]);

        return $this->successResponse(new PrinterResource($printer), 'Impresora obtenida exitosamente');
    }

    /**
     * Reportar latido y telemetría (Heartbeat).
     *
     * Consumido por el microcontrolador o agente local para reportar su estado operativo y telemetría hardware opcional.
     */
    public function heartbeat(
        PrinterHeartbeatRequest $request,
        Printer $printer,
        HardwareService $hardwareService
    ): JsonResponse {
        Gate::authorize('update', $printer);

        $this->storeDeviceInfoIfPresent($request, $hardwareService, (int) $printer->hardware_device_id);

        $this->printerService->recordHeartbeat($printer, $request->validated('status'));

        return $this->successResponse([
            'printer_id' => $printer->id,
            'status' => $printer->status->value,
            'last_seen_at' => $printer->last_seen_at?->toISOString(),
        ], 'Heartbeat registrado exitosamente');
    }
}
