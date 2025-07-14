<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use App\Models\Language;
use App\Models\Platform;

class NewsletterSubscribeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Comprueba si el idioma es válido, además lo busca de la request en caso de no recibirlo.
     * En la base de datos se busca en este orden: 'locale', 'iso_locale', 'iso2' (es_ES, es-ES, es)
     *
     * @param array|null $acceptLanguages Idiomas recibidos por el navegador del cliente en un array.
     * @param string|null $language Idioma a comprobar.
     *
     * @return string
     */
    public function searchLanguageHelper(array|null $acceptLanguages, string|null $language): string
    {
        if ($language) {
            foreach (['locale', 'iso_locale', 'iso2'] as $field) {
                $lang = Language::where($field, $language)->first();

                if ($lang) {
                    return $lang->iso2;
                }
            }
        }

        if (!empty($acceptLanguages)) {
            foreach ($acceptLanguages as $tupleLang) {
                $lang = Language::where('locale', $tupleLang['locale'])
                    ->orWhere('iso_locale', $tupleLang['iso_locale'])
                    ->orWhere('iso2', $tupleLang['iso2'])
                    ->first();

                if ($lang) {
                    return $lang->iso2;
                }
            }
        }

        return 'es'; // Idioma por defecto
    }

    /**
     * Busca en la cadena recibida el idioma y su puntuación.
     *
     * @param string $value Cadena con el idioma y su puntuación.
     *
     * @return array|null
     */
    public function mapExtractLanguages(string $value): ?array
    {
        $explode = explode(';', $value);

        if (!$explode || !is_array($explode) || !count($explode)) {
            return null;
        }

        $score = count($explode) === 2 ? (float)trim($explode[1]) : 1.0;
        $isoLocale = mb_strtolower(trim($explode[0]));

        if ($isoLocale === '*') {
            return null;
        }

        $iso2 = trim(substr($isoLocale, 0, 2));

        if (strlen($isoLocale) === 5) {
            $locale = $iso2 . '_' . strtoupper(substr($isoLocale, 3, 5));
        } else {
            $locale = $iso2 . '_' . strtoupper($iso2);
            $isoLocale = $iso2 . '-' . $iso2;
        }

        return [
            'locale' => $locale,
            'iso_locale' => $isoLocale,
            'iso2' => $iso2,
            'score' => $score,
        ];
    }

    /**
     * Devuelve un array con los idiomas que envía el cliente en la petición.
     *
     * @param Request $request Petición a comprobar.
     *
     * @return array
     */
    public function getClientLanguagesFromRequest(Request $request): array
    {
        $languages = $request->server('HTTP_ACCEPT_LANGUAGE');

        if (empty($languages)) {
            return [];
        }

        $array = explode(',', $languages);

        try {
            $arrayPrepared = array_map(fn ($value): ?array => $this->mapExtractLanguages($value) , $array);
            $arrayFilter = collect(array_filter($arrayPrepared));

            return $arrayFilter->sortByDesc('score')->unique('locale')->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $request = request();
        $arrayLanguagesFromRequest = $this->getClientLanguagesFromRequest($request);

        // Buscar platform_id basado en el dominio de la request
        $platformId = $this->getPlatformIdFromDomain($request->getHost());

        $this->merge([
            'platform_id' => $platformId,
            'language' => $this->searchLanguageHelper($arrayLanguagesFromRequest, $this->language ?? null),
            'email' => trim($this->email),
            'name' => trim($this->name ?? ''),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'subscription_source' => 'api',
            'client_accept_language' => json_encode($arrayLanguagesFromRequest),
        ]);
    }

    /**
     * Obtener platform_id basado en el dominio
     *
     * @param string $domain
     * @return int
     */
    private function getPlatformIdFromDomain(string $domain): int
    {
        $platform = Platform::where('domain', $domain)->first();

        if (!$platform) {
            // Si no encuentra la plataforma, usa la primera disponible o crea una por defecto
            $platform = Platform::first();
        }

        return $platform ? $platform->id : 1;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|string|max:255|email',
            'name' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:5',
            'ip_address' => 'nullable|string|max:45',
            'user_agent' => 'nullable|string|max:500',
            'platform_id' => 'required|integer|exists:platforms,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El formato del email no es válido',
            'email.max' => 'El email no puede tener más de 255 caracteres',
            'name.max' => 'El nombre no puede tener más de 100 caracteres',
            'language.max' => 'El idioma no puede tener más de 5 caracteres',
            'platform_id.required' => 'La plataforma es obligatoria',
            'platform_id.exists' => 'La plataforma especificada no existe',
        ];
    }
}
