<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Printers\V2;

use App\Http\Controllers\Api\Hardware\V2\Concerns\HandlesHardwareDeviceInfo;
use App\Http\Controllers\Api\V2\BaseApiController;
use App\Http\Requests\Api\Printers\V2\ClaimNextJobRequest;
use App\Http\Requests\Api\Printers\V2\EnqueuePrintJobRequest;
use App\Http\Requests\Api\Printers\V2\ReprintJobRequest;
use App\Http\Requests\Api\Printers\V2\ToggleFavoriteJobRequest;
use App\Http\Requests\Api\Printers\V2\UpdatePrintJobStatusRequest;
use App\Http\Resources\V2\Printers\PrintJobResource;
use App\Models\Printer;
use App\Models\PrinterStack;
use App\Models\User;
use App\Services\Hardware\HardwareService;
use App\Services\Printers\PrinterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Controlador API V2 para la cola de trabajos de impresión física.
 *
 * @group Impresoras
 */
class PrintJobController extends BaseApiController
{
    use HandlesHardwareDeviceInfo;

    public function __construct(
        protected PrinterService $printerService
    ) {}

    /**
     * Listar trabajos de una impresora.
     */
    public function index(Request $request, Printer $printer): JsonResponse
    {
        Gate::authorize('view', $printer);

        $filters = $request->only(['status', 'format', 'is_favorite']);
        $paginator = $this->printerService->getJobsForPrinter($printer, $filters);

        return $this->paginatedResponse($paginator, PrintJobResource::class, 'Trabajos de impresión obtenidos exitosamente');
    }

    /**
     * Listar plantillas favoritas de una impresora.
     */
    public function favorites(Printer $printer): JsonResponse
    {
        Gate::authorize('view', $printer);

        $paginator = $this->printerService->getFavoritesForPrinter($printer);

        return $this->paginatedResponse($paginator, PrintJobResource::class, 'Plantillas favoritas obtenidas exitosamente');
    }

    /**
     * Detalle de un trabajo concreto.
     */
    public function show(PrinterStack $job): JsonResponse
    {
        Gate::authorize('view', $job);

        return $this->successResponse(new PrintJobResource($job), 'Trabajo de impresión obtenido exitosamente');
    }

    /**
     * Encolar nuevo trabajo de impresión.
     */
    public function store(EnqueuePrintJobRequest $request, Printer $printer): JsonResponse
    {
        Gate::authorize('update', $printer);

        /** @var User|null $user */
        $user = $request->user();

        $job = $this->printerService->enqueueJob($printer, $request->validated(), $user);

        return $this->createdResponse(new PrintJobResource($job), 'Trabajo de impresión encolado exitosamente');
    }

    /**
     * Reclamar siguiente trabajo disponible de forma atómica.
     *
     * Consumido por el microcontrolador o agente físico para extraer un trabajo pendiente de la cola.
     */
    public function claimNext(
        ClaimNextJobRequest $request,
        Printer $printer,
        HardwareService $hardwareService
    ): JsonResponse {
        Gate::authorize('update', $printer);

        $this->storeDeviceInfoIfPresent($request, $hardwareService, (int) $printer->hardware_device_id);

        $job = $this->printerService->claimNextJob($printer);

        if (! $job) {
            return $this->successResponse(null, 'No hay trabajos pendientes en la cola');
        }

        return $this->successResponse(new PrintJobResource($job), 'Trabajo de impresión reclamado exitosamente');
    }

    /**
     * Notificar resultado de la impresión física.
     *
     * Consumido por el microcontrolador para reportar el éxito ('completed') o fallo ('failed')
     * de la ejecución del ticket.
     */
    public function updateStatus(
        UpdatePrintJobStatusRequest $request,
        PrinterStack $job,
        HardwareService $hardwareService
    ): JsonResponse {
        Gate::authorize('update', $job);

        $this->storeDeviceInfoIfPresent($request, $hardwareService, (int) $job->printer->hardware_device_id);

        $updatedJob = $this->printerService->updateJobStatus(
            $job,
            $request->validated('status'),
            $request->validated('error_message')
        );

        return $this->successResponse(new PrintJobResource($updatedJob), 'Estado del trabajo de impresión actualizado exitosamente');
    }

    /**
     * Reimprimir un trabajo existente duplicándolo en la cola.
     */
    public function reprint(ReprintJobRequest $request, PrinterStack $job): JsonResponse
    {
        Gate::authorize('update', $job);

        /** @var User|null $user */
        $user = $request->user();

        $newJob = $this->printerService->reprintJob($job, $request->input('priority'), $user);

        return $this->createdResponse(new PrintJobResource($newJob), 'Trabajo de impresión reencolado exitosamente');
    }

    /**
     * Alternar o marcar trabajo como favorito / plantilla.
     */
    public function toggleFavorite(ToggleFavoriteJobRequest $request, PrinterStack $job): JsonResponse
    {
        Gate::authorize('update', $job);

        $isFavorite = $request->has('is_favorite') ? (bool) $request->input('is_favorite') : null;
        $updatedJob = $this->printerService->toggleFavorite($job, $isFavorite);

        return $this->successResponse(new PrintJobResource($updatedJob), 'Estado favorito actualizado exitosamente');
    }

    /**
     * Cancelar o eliminar trabajo de impresión.
     */
    public function destroy(PrinterStack $job): JsonResponse
    {
        Gate::authorize('delete', $job);

        $cancelledJob = $this->printerService->cancelJob($job);

        return $this->successResponse(new PrintJobResource($cancelledJob), 'Trabajo de impresión cancelado exitosamente');
    }
}
