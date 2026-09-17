<?php

declare(strict_types=1);

namespace App\DTO\Hardware;

use App\Models\Hardware\HardwareDevice;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;

use function intdiv;

/**
 * DTO inmutable para exponer dispositivos hardware en la web pública.
 *
 * Aplica una política estricta de Privacy by Design:
 * - Excluye totalmente: ip_local, ip_public, serial_number, ref, buy_at, user_id, extra, deleted_at.
 * - Mantiene únicamente identidad tecnológica, salud, telemetría de placa y componentes acoplados.
 */
readonly class PublicHardwareDeviceData
{
    /**
     * @param  array<int, array{name: string, brand: ?string, model: ?string, quantity: ?string, power: ?string, description: ?string, available_name: ?string, available_type: ?string}>  $components
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $slug,
        public ?string $nameFriendly,
        public string $displayName,
        public ?string $brand,
        public ?string $model,
        public ?string $typeName,
        public ?string $typeSlug,
        public ?string $softwareVersion,
        public ?string $hardwareVersion,
        public ?string $urlCompany,
        public ?string $description,
        public ?string $imageUrl,
        public ?string $imageThumbnailUrl,
        public ?string $locationTypeLabel,
        public bool $isOnline,
        public ?string $lastSeenDiff,
        public ?string $lastSeenFormatted,
        public ?int $uptimeSeconds,
        public ?string $uptimeFormatted,
        public ?float $temp,
        public ?string $tempColor,
        public ?float $cpu,
        public ?float $ram,
        public ?float $disk,
        public ?float $voltage,
        public ?int $batteryLevel,
        public ?float $batteryVoltage,
        public ?string $batteryType,
        public ?int $batteryNominalCapacity,
        public array $components,
    ) {}

    public static function fromModel(HardwareDevice $device): self
    {
        $lastSeen = $device->last_seen_at;
        $isOnline = $lastSeen instanceof SupportCarbon && $lastSeen->greaterThanOrEqualTo(Carbon::now()->subHours(2));

        $temp = $device->temp !== null ? (float) $device->temp : null;
        $tempColor = match (true) {
            $temp === null => null,
            $temp < 45.0 => 'emerald',
            $temp < 65.0 => 'amber',
            default => 'rose',
        };

        $uptimeFormatted = $device->uptime !== null ? self::formatUptime((int) $device->uptime) : null;

        $components = $device->components->map(static function ($component) {
            return [
                'name' => (string) ($component->name ?: ($component->availableComponent->name ?? 'Componente')),
                'brand' => $component->brand,
                'model' => $component->model,
                'quantity' => $component->quantity,
                'power' => $component->power,
                'description' => $component->description,
                'available_name' => $component->availableComponent?->name,
                'available_type' => $component->availableComponent?->type,
            ];
        })->values()->all();

        return new self(
            id: (int) $device->id,
            name: (string) $device->name,
            slug: (string) $device->slug,
            nameFriendly: $device->name_friendly,
            displayName: $device->display_name,
            brand: $device->brand,
            model: $device->model,
            typeName: $device->type?->name,
            typeSlug: $device->type?->slug,
            softwareVersion: $device->software_version,
            hardwareVersion: $device->hardware_version,
            urlCompany: $device->url_company,
            description: $device->description,
            imageUrl: $device->url_image ?: null,
            imageThumbnailUrl: $device->url_image_medium ?: ($device->url_image ?: null),
            locationTypeLabel: $device->location_label,
            isOnline: $isOnline,
            lastSeenDiff: $lastSeen?->diffForHumans(),
            lastSeenFormatted: $lastSeen?->format('d/m/Y H:i'),
            uptimeSeconds: $device->uptime !== null ? (int) $device->uptime : null,
            uptimeFormatted: $uptimeFormatted,
            temp: $temp,
            tempColor: $tempColor,
            cpu: $device->cpu !== null ? (float) $device->cpu : null,
            ram: $device->ram !== null ? (float) $device->ram : null,
            disk: $device->disk !== null ? (float) $device->disk : null,
            voltage: $device->voltage !== null ? (float) $device->voltage : null,
            batteryLevel: $device->battery_level !== null ? (int) $device->battery_level : null,
            batteryVoltage: $device->battery_voltage !== null ? (float) $device->battery_voltage : null,
            batteryType: $device->battery_type,
            batteryNominalCapacity: $device->battery_nominal_capacity !== null ? (int) $device->battery_nominal_capacity : null,
            components: $components,
        );
    }

    /**
     * Formatea segundos de actividad a un texto amigable en español.
     */
    private static function formatUptime(int $seconds): string
    {
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($days > 0) {
            return $hours > 0 ? "{$days}d {$hours}h" : "{$days}d";
        }

        if ($hours > 0) {
            return $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";
        }

        return "{$minutes}m";
    }
}
