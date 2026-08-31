<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\Rules\Password;

/**
 * Reglas de validación reutilizables para formularios Filament.
 */
class FilamentValidationRules
{
    /**
     * Slug alfanumérico con guiones.
     */
    public static function slug(): array
    {
        return ['required', 'string', 'max:255', 'regex:/^[a-z0-9\-]+$/'];
    }

    /**
     * Color hexadecimal (#RGB o #RRGGBB).
     */
    public static function hexColor(): array
    {
        return ['required', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'];
    }

    /**
     * Dirección IPv4 opcional.
     */
    public static function ipv4(): array
    {
        return ['nullable', 'ipv4'];
    }

    /**
     * Dirección MAC opcional.
     */
    public static function mac(): array
    {
        return ['nullable', 'regex:/^([0-9A-Fa-f]{2}[:\-]){5}([0-9A-Fa-f]{2})$/'];
    }

    /**
     * Contraseña segura estándar.
     *
     * Parte de la política global (`Password::defaults()`, definida en
     * `AppServiceProvider`) y le añade símbolos. Así hay un único sitio donde
     * subir el listón para todo el proyecto.
     */
    public static function passwordStrong(): Password
    {
        return Password::default()->symbols();
    }
}
