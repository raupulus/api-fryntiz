<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V2;

use App\Enums\PrinterStatusEnum;
use App\Enums\PrinterTypeEnum;
use App\Enums\PrintJobFormatEnum;
use App\Enums\PrintJobStatusEnum;
use App\Enums\UserRoleEnum;
use App\Events\Printers\PrintJobCreated;
use App\Events\Printers\PrintJobStatusUpdated;
use App\Models\Hardware\HardwareDevice;
use App\Models\Printer;
use App\Models\PrinterStack;
use App\Models\User;
use App\Support\Auth\TokenAbilities;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Api\ApiTestCase;

class PrintersTest extends ApiTestCase
{
    protected string $apiPrefix = 'api/v2';

    private function makePrinter(User $user, array $attributes = []): Printer
    {
        $device = HardwareDevice::create([
            'user_id' => $user->id,
            'name' => 'Dispositivo Impresora '.uniqid(),
        ]);

        return Printer::create(array_merge([
            'hardware_device_id' => $device->id,
            'name' => 'Impresora Térmica '.uniqid(),
            'printer_type' => PrinterTypeEnum::Thermal,
            'is_active' => true,
            'status' => PrinterStatusEnum::Ready,
            'supported_formats' => [PrintJobFormatEnum::Text->value, PrintJobFormatEnum::Escpos->value],
            'default_format' => PrintJobFormatEnum::Text,
            'max_payload_kb' => 64,
            'total_prints_count' => 0,
        ], $attributes));
    }

    #[Test]
    public function cannot_access_printers_unauthenticated(): void
    {
        $response = $this->getJson($this->apiUrl('printers'), $this->guestHeaders());
        $this->assertErrorResponse($response, 401);
    }

    #[Test]
    public function user_can_list_own_printers(): void
    {
        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user, ['name' => 'Mi Impresora']);

        $response = $this->getJson(
            $this->apiUrl('printers'),
            $this->authenticatedHeaders($user)
        );

        $this->assertPaginatedResponse($response);
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('Mi Impresora', $response->json('data.0.name'));
    }

    #[Test]
    public function user_cannot_see_printers_of_other_users(): void
    {
        $userA = $this->createAuthenticatedUser();
        $userB = $this->createAuthenticatedUser();

        $this->makePrinter($userA, ['name' => 'Impresora A']);
        $this->makePrinter($userB, ['name' => 'Impresora B']);

        $response = $this->getJson(
            $this->apiUrl('printers'),
            $this->authenticatedHeaders($userA)
        );

        $this->assertPaginatedResponse($response);
        $this->assertSame(1, $response->json('meta.total'));
        $this->assertSame('Impresora A', $response->json('data.0.name'));
    }

    #[Test]
    public function admin_can_see_all_printers(): void
    {
        $admin = $this->createAuthenticatedUser(UserRoleEnum::Admin->value);
        $userA = $this->createAuthenticatedUser();
        $userB = $this->createAuthenticatedUser();

        $this->makePrinter($userA);
        $this->makePrinter($userB);

        $response = $this->getJson(
            $this->apiUrl('printers'),
            $this->authenticatedHeaders($admin)
        );

        $this->assertPaginatedResponse($response);
        $this->assertSame(2, $response->json('meta.total'));
    }

    #[Test]
    public function user_can_view_own_printer_details(): void
    {
        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user);

        $response = $this->getJson(
            $this->apiUrl("printers/{$printer->id}"),
            $this->authenticatedHeaders($user)
        );

        $this->assertSuccessResponse($response);
        $this->assertSame($printer->id, $response->json('data.id'));
        $this->assertSame('thermal', $response->json('data.printer_type'));
    }

    #[Test]
    public function user_cannot_view_other_user_printer(): void
    {
        $userA = $this->createAuthenticatedUser();
        $userB = $this->createAuthenticatedUser();
        $printerB = $this->makePrinter($userB);

        $response = $this->getJson(
            $this->apiUrl("printers/{$printerB->id}"),
            $this->authenticatedHeaders($userA)
        );

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function user_can_enqueue_print_job_and_dispatches_event(): void
    {
        Event::fake([PrintJobCreated::class]);

        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user);

        $response = $this->postJson(
            $this->apiUrl("printers/{$printer->id}/jobs"),
            [
                'content' => 'Comanda mesa #4',
                'format' => 'text',
                'priority' => 10,
                'note' => 'Cocina',
                'is_favorite' => true,
            ],
            $this->authenticatedHeaders($user)
        );

        $this->assertSuccessResponse($response, 201);
        $this->assertSame('pending', $response->json('data.status'));
        $this->assertSame(10, $response->json('data.priority'));
        $this->assertTrue($response->json('data.is_favorite'));

        $this->assertDatabaseHas('printer_stack', [
            'printer_id' => $printer->id,
            'content' => 'Comanda mesa #4',
            'status' => 'pending',
            'priority' => 10,
        ]);

        Event::assertDispatched(PrintJobCreated::class, function ($event) use ($printer) {
            return $event->printerId === $printer->id;
        });
    }

    #[Test]
    public function enqueue_job_validates_unsupported_format(): void
    {
        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user, [
            'supported_formats' => ['text'],
        ]);

        $response = $this->postJson(
            $this->apiUrl("printers/{$printer->id}/jobs"),
            [
                'content' => 'G1 X10 Y20',
                'format' => 'gcode',
            ],
            $this->authenticatedHeaders($user)
        );

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['format']);
    }

    #[Test]
    public function enqueue_job_validates_max_payload_kb(): void
    {
        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user, [
            'max_payload_kb' => 1, // 1024 bytes
        ]);

        $excessiveContent = str_repeat('A', 2000);

        $response = $this->postJson(
            $this->apiUrl("printers/{$printer->id}/jobs"),
            [
                'content' => $excessiveContent,
                'format' => 'text',
            ],
            $this->authenticatedHeaders($user)
        );

        $this->assertErrorResponse($response, 422);
        $response->assertJsonValidationErrors(['content']);
    }

    #[Test]
    public function device_can_claim_next_job_atomically_with_telemetry(): void
    {
        Event::fake([PrintJobStatusUpdated::class]);

        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user);

        // Creamos dos trabajos con distinta prioridad
        PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Ticket 1 Baja Prioridad',
            'priority' => 0,
            'status' => PrintJobStatusEnum::Pending,
        ]);
        $highPriorityJob = PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Ticket 2 Alta Prioridad',
            'priority' => 10,
            'status' => PrintJobStatusEnum::Pending,
        ]);

        $deviceHeaders = $this->deviceHeaders($printer->hardwareDevice, [TokenAbilities::PRINTERS_WRITE]);

        $response = $this->postJson(
            $this->apiUrl("printers/{$printer->id}/jobs/next"),
            [
                'hardware_device_info' => [
                    'temp' => 41.5,
                    'uptime' => 1200,
                ],
            ],
            $deviceHeaders
        );

        $this->assertSuccessResponse($response);
        $this->assertSame($highPriorityJob->id, $response->json('data.id'));
        $this->assertSame('processing', $response->json('data.status'));
        $this->assertSame(1, $response->json('data.attempts'));

        // Comprobamos telemetría guardada
        $this->assertDatabaseHas('hardware_devices', [
            'id' => $printer->hardware_device_id,
            'temp' => 41.5,
            'uptime' => 1200,
        ]);

        Event::assertDispatched(PrintJobStatusUpdated::class, function ($event) use ($printer, $highPriorityJob) {
            return $event->printerId === $printer->id && $event->jobId === $highPriorityJob->id;
        });
    }

    #[Test]
    public function device_receives_null_when_queue_is_empty(): void
    {
        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user);
        $deviceHeaders = $this->deviceHeaders($printer->hardwareDevice, [TokenAbilities::PRINTERS_WRITE]);

        $response = $this->postJson(
            $this->apiUrl("printers/{$printer->id}/jobs/next"),
            [],
            $deviceHeaders
        );

        $this->assertSuccessResponse($response);
        $this->assertNull($response->json('data'));
    }

    #[Test]
    public function device_cannot_claim_jobs_from_another_device(): void
    {
        $user = $this->createAuthenticatedUser();
        $printerA = $this->makePrinter($user);
        $printerB = $this->makePrinter($user);

        // Token para dispositivo A intenta reclamar impresora B
        $deviceHeadersA = $this->deviceHeaders($printerA->hardwareDevice, [TokenAbilities::PRINTERS_WRITE]);

        $response = $this->postJson(
            $this->apiUrl("printers/{$printerB->id}/jobs/next"),
            [],
            $deviceHeadersA
        );

        $this->assertErrorResponse($response, 403);
    }

    #[Test]
    public function device_can_report_completed_status_incrementing_odometer(): void
    {
        Event::fake([PrintJobStatusUpdated::class]);

        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user, ['total_prints_count' => 5]);
        $job = PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Recibo de compra',
            'status' => PrintJobStatusEnum::Processing,
            'attempts' => 1,
            'print_count' => 0,
        ]);

        $deviceHeaders = $this->deviceHeaders($printer->hardwareDevice, [TokenAbilities::PRINTERS_WRITE]);

        $response = $this->patchJson(
            $this->apiUrl("printers/jobs/{$job->id}/status"),
            [
                'status' => 'completed',
            ],
            $deviceHeaders
        );

        $this->assertSuccessResponse($response);
        $this->assertSame('completed', $response->json('data.status'));
        $this->assertSame(1, $response->json('data.print_count'));

        $this->assertDatabaseHas('printer_stack', [
            'id' => $job->id,
            'status' => 'completed',
            'print_count' => 1,
        ]);

        $this->assertDatabaseHas('printers', [
            'id' => $printer->id,
            'total_prints_count' => 6,
        ]);

        Event::assertDispatched(PrintJobStatusUpdated::class);
    }

    #[Test]
    public function device_can_send_heartbeat(): void
    {
        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user, ['status' => PrinterStatusEnum::Offline]);
        $deviceHeaders = $this->deviceHeaders($printer->hardwareDevice, [TokenAbilities::PRINTERS_WRITE]);

        $response = $this->postJson(
            $this->apiUrl("printers/{$printer->id}/heartbeat"),
            [
                'status' => 'ready',
            ],
            $deviceHeaders
        );

        $this->assertSuccessResponse($response);
        $this->assertSame('ready', $response->json('data.status'));

        $this->assertDatabaseHas('printers', [
            'id' => $printer->id,
            'status' => 'ready',
        ]);
    }

    #[Test]
    public function user_can_reprint_job(): void
    {
        Event::fake([PrintJobCreated::class]);

        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user);
        $job = PrinterStack::create([
            'printer_id' => $printer->id,
            'user_id' => $user->id,
            'content' => 'Ticket a duplicar',
            'note' => 'Original',
            'status' => PrintJobStatusEnum::Completed,
            'print_count' => 1,
        ]);

        $response = $this->postJson(
            $this->apiUrl("printers/jobs/{$job->id}/reprint"),
            ['priority' => 15],
            $this->authenticatedHeaders($user)
        );

        $this->assertSuccessResponse($response, 201);
        $this->assertSame('pending', $response->json('data.status'));
        $this->assertSame(15, $response->json('data.priority'));
        $this->assertSame(0, $response->json('data.print_count'));
        $this->assertNotSame($job->id, $response->json('data.id'));

        Event::assertDispatched(PrintJobCreated::class);
    }

    #[Test]
    public function user_can_toggle_favorite_job(): void
    {
        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user);
        $job = PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Plantilla ticket',
            'is_favorite' => false,
            'status' => PrintJobStatusEnum::Pending,
        ]);

        $response = $this->patchJson(
            $this->apiUrl("printers/jobs/{$job->id}/favorite"),
            ['is_favorite' => true],
            $this->authenticatedHeaders($user)
        );

        $this->assertSuccessResponse($response);
        $this->assertTrue($response->json('data.is_favorite'));
    }

    #[Test]
    public function user_can_cancel_pending_job(): void
    {
        $user = $this->createAuthenticatedUser();
        $printer = $this->makePrinter($user);
        $job = PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Trabajo a cancelar',
            'status' => PrintJobStatusEnum::Pending,
        ]);

        $response = $this->deleteJson(
            $this->apiUrl("printers/jobs/{$job->id}"),
            [],
            $this->authenticatedHeaders($user)
        );

        $this->assertSuccessResponse($response);
        $this->assertSame('cancelled', $response->json('data.status'));
    }
}
