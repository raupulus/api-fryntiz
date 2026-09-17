<?php

declare(strict_types=1);

namespace Tests\Unit\Printers;

use App\Enums\PrinterStatusEnum;
use App\Enums\PrinterTypeEnum;
use App\Enums\PrintJobFormatEnum;
use App\Enums\PrintJobStatusEnum;
use App\Models\Hardware\HardwareDevice;
use App\Models\Printer;
use App\Models\PrinterStack;
use App\Models\User;
use App\Services\Printers\PrinterService;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class PrinterServiceTest extends TestCase
{
    use RefreshDatabase;

    private PrinterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesTableSeeder)->run();
        $this->service = new PrinterService;
    }

    private function createPrinter(array $attributes = []): Printer
    {
        $user = User::factory()->create();
        $device = HardwareDevice::create([
            'user_id' => $user->id,
            'name' => 'Host Device',
        ]);

        return Printer::create(array_merge([
            'hardware_device_id' => $device->id,
            'name' => 'EM5820 Térmica',
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
    public function enqueue_job_throws_when_printer_inactive(): void
    {
        $printer = $this->createPrinter(['is_active' => false]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La impresora seleccionada no está activa');

        $this->service->enqueueJob($printer, ['content' => 'Test']);
    }

    #[Test]
    public function enqueue_job_throws_when_format_not_supported(): void
    {
        $printer = $this->createPrinter(['supported_formats' => ['text']]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("El formato 'gcode' no está soportado");

        $this->service->enqueueJob($printer, ['content' => 'G1 X0', 'format' => 'gcode']);
    }

    #[Test]
    public function claim_next_job_selects_highest_priority_and_oldest_first(): void
    {
        $printer = $this->createPrinter();

        // Trabajo 1: prioridad baja, creado primero
        $jobLow = PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Baja prioridad',
            'priority' => 0,
            'created_at' => Carbon::now()->subMinutes(10),
            'status' => PrintJobStatusEnum::Pending,
        ]);

        // Trabajo 2: prioridad alta, creado después
        $jobHigh = PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Alta prioridad',
            'priority' => 50,
            'created_at' => Carbon::now()->subMinutes(5),
            'status' => PrintJobStatusEnum::Pending,
        ]);

        $claimed = $this->service->claimNextJob($printer);

        $this->assertNotNull($claimed);
        $this->assertSame($jobHigh->id, $claimed->id);
        $this->assertSame(PrintJobStatusEnum::Processing, $claimed->status);
        $this->assertSame(1, $claimed->attempts);

        // El siguiente debe ser el de baja prioridad
        $claimedSecond = $this->service->claimNextJob($printer);
        $this->assertNotNull($claimedSecond);
        $this->assertSame($jobLow->id, $claimedSecond->id);

        // La cola queda vacía
        $this->assertNull($this->service->claimNextJob($printer));
    }

    #[Test]
    public function update_job_status_completed_increments_odometer_and_print_count(): void
    {
        $printer = $this->createPrinter(['total_prints_count' => 10]);
        $job = PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Imprimir',
            'status' => PrintJobStatusEnum::Processing,
            'print_count' => 0,
        ]);

        $updated = $this->service->updateJobStatus($job, PrintJobStatusEnum::Completed);

        $this->assertSame(PrintJobStatusEnum::Completed, $updated->status);
        $this->assertSame(1, $updated->print_count);
        $this->assertNotNull($updated->printed_at);

        $printer->refresh();
        $this->assertSame(11, $printer->total_prints_count);
    }

    #[Test]
    public function toggle_favorite_alternates_status(): void
    {
        $printer = $this->createPrinter();
        $job = PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Plantilla',
            'is_favorite' => false,
        ]);

        $this->service->toggleFavorite($job);
        $this->assertTrue($job->fresh()->is_favorite);

        $this->service->toggleFavorite($job);
        $this->assertFalse($job->fresh()->is_favorite);
    }
}
