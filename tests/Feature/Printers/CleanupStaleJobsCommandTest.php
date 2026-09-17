<?php

declare(strict_types=1);

namespace Tests\Feature\Printers;

use App\Enums\PrinterStatusEnum;
use App\Enums\PrinterTypeEnum;
use App\Enums\PrintJobFormatEnum;
use App\Enums\PrintJobStatusEnum;
use App\Models\Hardware\HardwareDevice;
use App\Models\Printer;
use App\Models\PrinterStack;
use App\Models\User;
use Database\Seeders\RolesTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CleanupStaleJobsCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        (new RolesTableSeeder)->run();
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
            'supported_formats' => [PrintJobFormatEnum::Text->value],
            'default_format' => PrintJobFormatEnum::Text,
            'max_payload_kb' => 64,
            'total_prints_count' => 0,
            'last_seen_at' => Carbon::now(),
        ], $attributes));
    }

    #[Test]
    public function it_requeues_processing_jobs_under_three_attempts(): void
    {
        $printer = $this->createPrinter();
        $staleJob = PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Trabajo colgado',
            'status' => PrintJobStatusEnum::Processing,
            'attempts' => 1,
        ]);
        PrinterStack::where('id', $staleJob->id)->update(['updated_at' => Carbon::now()->subMinutes(20)]);

        $this->artisan('printers:cleanup-stale-jobs --minutes=15')
            ->assertSuccessful();

        $staleJob->refresh();
        $this->assertSame(PrintJobStatusEnum::Pending, $staleJob->status);
        $this->assertStringContainsString('Reencolado automáticamente', (string) $staleJob->error_message);
    }

    #[Test]
    public function it_marks_as_failed_processing_jobs_at_or_above_three_attempts(): void
    {
        $printer = $this->createPrinter();
        $staleJob = PrinterStack::create([
            'printer_id' => $printer->id,
            'content' => 'Trabajo agotado',
            'status' => PrintJobStatusEnum::Processing,
            'attempts' => 3,
        ]);
        PrinterStack::where('id', $staleJob->id)->update(['updated_at' => Carbon::now()->subMinutes(20)]);

        $this->artisan('printers:cleanup-stale-jobs --minutes=15')
            ->assertSuccessful();

        $staleJob->refresh();
        $this->assertSame(PrintJobStatusEnum::Failed, $staleJob->status);
        $this->assertStringContainsString('Tiempo de espera agotado', (string) $staleJob->error_message);
    }

    #[Test]
    public function it_marks_inactive_printers_as_offline(): void
    {
        $printer = $this->createPrinter([
            'status' => PrinterStatusEnum::Ready,
            'last_seen_at' => Carbon::now()->subMinutes(15),
        ]);

        $this->artisan('printers:cleanup-stale-jobs')
            ->assertSuccessful();

        $printer->refresh();
        $this->assertSame(PrinterStatusEnum::Offline, $printer->status);
    }
}
