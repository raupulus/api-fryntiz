<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Newsletter\V2;

use App\Http\Requests\Api\BaseFormRequest;
use App\Models\Platform;
use App\Support\Http\ClientIp;

/**
 * Validación para suscripción a newsletter en API V2.
 *
 * `platform_id` se valida aquí. Antes entraba por `request()` dentro del
 * servicio sin ninguna regla, así que un id inexistente reventaba contra la
 * clave foránea y salía un 500 en lugar de un 422 (auditoría A7). Y el mismo
 * campo sí se validaba en `NewsletterResendRequest`: dos criterios distintos
 * para el mismo dato en el mismo módulo.
 *
 * Si el cliente no lo manda, se resuelve por el dominio de la petición.
 */
class NewsletterSubscribeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('platform_id')) {
            return;
        }

        $platform = Platform::query()
            ->where('domain', $this->getHost())
            ->first();

        if ($platform) {
            $this->merge(['platform_id' => $platform->id]);
        }
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
            'platform_id' => ['required', 'integer', 'exists:platforms,id'],
        ];
    }

    /**
     * Sólo lo que el mensaje por defecto no puede decir.
     *
     * El resto salía de aquí escrito a mano —98 cadenas repartidas por 19
     * ficheros, la mitad sin tildes y todas sólo en español— para acabar
     * diciendo lo mismo que ya dice `lang/{es,en}/validation.php`. Los nombres
     * de campo viven ahora en su bloque `attributes`, así que «El campo
     * hardware_device_id es obligatorio» sale ya como «El campo dispositivo es
     * obligatorio», en los dos idiomas y para todas las reglas.
     *
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'platform_id.required' => 'No se ha podido determinar la plataforma de la suscripción.',
        ];
    }

    /**
     * Datos de contexto que el servicio guarda con la suscripción.
     *
     * @return array{language: string, ip_address: string|null, user_agent: string|null}
     */
    public function context(): array
    {
        $locale = $this->header('Accept-Language');

        return [
            'language' => $locale ? substr($locale, 0, 2) : 'es',
            // La de origen, no la del proxy: se guarda para poder rastrear
            // una suscripción y detectar altas masivas.
            'ip_address' => ClientIp::public($this) ?? $this->ip(),
            'user_agent' => $this->userAgent(),
        ];
    }
}
