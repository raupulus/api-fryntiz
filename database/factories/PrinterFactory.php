<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PrinterStatusEnum;
use App\Enums\PrinterTypeEnum;
use App\Enums\PrintJobFormatEnum;
use App\Models\Hardware\HardwareDevice;
use App\Models\Printer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Printer>
 */
class PrinterFactory extends Factory
{
    protected $model = Printer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hardware_device_id' => HardwareDevice::factory(),
            'name' => 'Impresora '.$this->faker->words(2, true),
            'code' => 'PRN-'.$this->faker->unique()->numerify('####'),
            'description' => $this->faker->sentence(),
            'printer_type' => PrinterTypeEnum::Thermal,
            'is_active' => true,
            'status' => PrinterStatusEnum::Ready,
            'supported_formats' => [PrintJobFormatEnum::Text->value, PrintJobFormatEnum::Escpos->value],
            'default_format' => PrintJobFormatEnum::Text,
            'max_payload_kb' => 64,
            'total_prints_count' => 0,
            'last_seen_at' => now(),
        ];
    }

    public function offline(): static
    {
        return $this->state(fn () => [
            'status' => PrinterStatusEnum::Offline,
        ]);
    }

    public function outOfPaper(): static
    {
        return $this->state(fn () => [
            'status' => PrinterStatusEnum::OutOfPaper,
        ]);
    }
}
