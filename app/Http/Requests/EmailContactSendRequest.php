<?php

namespace App\Http\Requests;

use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use function auth;

/**
 * Request para crear un nuevo tag.
 */
class EmailContactSendRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {

        return true;

        //return auth()->id() && auth()->user()->can('store', \App\Models\Tag::class);
    }

    /**
     * Comprueba si el idioma es válido, además lo busca de la request en caso de no recibirlo.
     * En la base de datos se busca en este orden: 'locale', 'iso_locale', 'iso2' (es_ES, es-ES, es)
     *
     * @param array|null $acceptLanguages Idiomas recibidos por el navegador del cliente en un array.
     * @param string|null $language Idioma a comprobar.
     *
     * @return int|null
     */
    public function searchLanguageHelper(array|null $acceptLanguages, string|null $language): ?int
    {
        if ($language) {
            foreach (['locale', 'iso_locale', 'iso2'] as $field) {
                $lang = Language::where($field, $language)->first();

                if ($lang) {
                    return $lang->id;
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
                    return $lang->id;
                }

            }
        }

        return null;
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

        ## Cuando no hay puntuación, se asigna 1.0 https://developer.mozilla.org/en-US/docs/Glossary/Quality_values
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
            'locale' => $locale, // 'es_ES'
            'iso_locale' => $isoLocale, // 'es-es'
            'iso2' => $iso2, // 'es'
            'score' => $score, // '0.8'
        ];
    }

    /**
     * Devuelve un array con los idiomas que envía el cliente en la petición.
     * Esto lo hace a partir de la cabecera HTTP_ACCEPT_LANGUAGE que envía el cliente (Navegador).
     *
     * @param Request $request Petición a comprobar.
     *
     * @return array
     */
    public function getClientLanguagesFromRequest(Request $request): array
    {
        $languages = $request->server('HTTP_ACCEPT_LANGUAGE'); // 'en-us,en;q=0.8,es-es;q=0.5,zh-cn;q=0.3,fr;q=0.1';

        if (empty($languages)) {
            return [];
        }

        $array = explode(',', $languages); // ['en-us', 'en;q=0.8', 'es-es;q=0.5', 'zh-cn;q=0.3', 'fr;q=0.1']

        try {
            $arrayPrepared = array_map(fn ($value): array => $this->mapExtractLanguages($value) , $array);

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

        $this->merge([
            'user_id' => auth()->id(),
            'language_id' => $this->searchLanguageHelper($arrayLanguagesFromRequest, $this->language ?? null) ?? 1,
            'privacity' => (bool)$this->privacity,
            'contactme' => (bool)$this->contactme,
            'message' => trim($this->message),
            'subject' => trim($this->subject),
            'email' => trim($this->email),
            'token' => trim($this->token),

            'app_name' => trim($this->app_name),
            'app_domain' => trim($this->app_domain),

            //'attributes' => $this->attributes ? json_encode($this->attributes) : null,

            //'server_ip' => null,
            'client_ip' => $request->ip(),
            'client_user_agent' => $request->userAgent(),
            'client_referer' => $request->server('HTTP_REFERER'),
            'client_accept_language' => json_encode($arrayLanguagesFromRequest),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'user_id' => 'nullable|integer|exists:users,id',
            'language_id' => 'nullable|integer|exists:languages,id',
            'message' => 'required|string|max:1024',
            'subject' => 'required|max:255',
            'email' => 'required|string|max:255|email',
            'privacity' => 'boolean',
            'contactme' => 'boolean',
            'captcha_token' => 'required',
            'attributes' => 'nullable|json',

            'app_name' => 'nullable|string|max:255',
            'app_domain' => 'nullable|string|max:255',

            'client_ip' => 'nullable|string|max:255',
            'client_user_agent' => 'nullable|string|max:255',
            'client_referer' => 'nullable|string|max:255',
            'client_accept_language' => 'nullable|json',
        ];
    }
}
