<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PrintJobFormatEnum;
use App\Enums\PrintJobStatusEnum;
use App\Models\Printer;
use App\Models\PrinterStack;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrinterStack>
 */
class PrinterStackFactory extends Factory
{
    protected $model = PrinterStack::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'printer_id' => Printer::factory(),
            'user_id' => User::factory(),
            'note' => $this->faker->sentence(3),
            'content' => $this->faker->paragraph(),
            'status' => PrintJobStatusEnum::Pending,
            'format' => PrintJobFormatEnum::Text,
            'priority' => 0,
            'attempts' => 0,
            'print_count' => 0,
            'is_favorite' => false,
            'error_message' => null,
            'printed_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => PrintJobStatusEnum::Processing,
            'attempts' => 1,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => PrintJobStatusEnum::Completed,
            'print_count' => 1,
            'printed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => PrintJobStatusEnum::Failed,
            'attempts' => 3,
            'error_message' => 'Error de prueba en la impresión física',
        ]);
    }

    public function favorite(): static
    {
        return $this->state(fn () => [
            'is_favorite' => true,
        ]);
    }
}
