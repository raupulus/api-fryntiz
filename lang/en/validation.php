<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'after_or_equal' => 'The :attribute must be a date after or equal to :date.',
    'alpha' => 'The :attribute must only contain letters.',
    'alpha_dash' => 'The :attribute must only contain letters, numbers, dashes and underscores.',
    'alpha_num' => 'The :attribute must only contain letters and numbers.',
    'array' => 'The :attribute must be an array.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equal' => 'The :attribute must be a date before or equal to :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min and :max.',
        'file' => 'The :attribute must be between :min and :max kilobytes.',
        'string' => 'The :attribute must be between :min and :max characters.',
        'array' => 'The :attribute must have between :min and :max items.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'date' => 'The :attribute is not a valid date.',
    'date_equals' => 'The :attribute must be a date equal to :date.',
    'date_format' => 'The :attribute does not match the format :format.',
    'different' => 'The :attribute and :other must be different.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'email' => 'The :attribute must be a valid email address.',
    'ends_with' => 'The :attribute must end with one of the following: :values.',
    'exists' => 'The selected :attribute is invalid.',
    'file' => 'The :attribute must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'numeric' => 'The :attribute must be greater than :value.',
        'file' => 'The :attribute must be greater than :value kilobytes.',
        'string' => 'The :attribute must be greater than :value characters.',
        'array' => 'The :attribute must have more than :value items.',
    ],
    'gte' => [
        'numeric' => 'The :attribute must be greater than or equal :value.',
        'file' => 'The :attribute must be greater than or equal :value kilobytes.',
        'string' => 'The :attribute must be greater than or equal :value characters.',
        'array' => 'The :attribute must have :value items or more.',
    ],
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field does not exist in :other.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'ipv4' => 'The :attribute must be a valid IPv4 address.',
    'ipv6' => 'The :attribute must be a valid IPv6 address.',
    'json' => 'The :attribute must be a valid JSON string.',
    'lt' => [
        'numeric' => 'The :attribute must be less than :value.',
        'file' => 'The :attribute must be less than :value kilobytes.',
        'string' => 'The :attribute must be less than :value characters.',
        'array' => 'The :attribute must have less than :value items.',
    ],
    'lte' => [
        'numeric' => 'The :attribute must be less than or equal :value.',
        'file' => 'The :attribute must be less than or equal :value kilobytes.',
        'string' => 'The :attribute must be less than or equal :value characters.',
        'array' => 'The :attribute must not have more than :value items.',
    ],
    'max' => [
        'numeric' => 'The :attribute must not be greater than :max.',
        'file' => 'The :attribute must not be greater than :max kilobytes.',
        'string' => 'The :attribute must not be greater than :max characters.',
        'array' => 'The :attribute must not have more than :max items.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'string' => 'The :attribute must be at least :min characters.',
        'array' => 'The :attribute must have at least :min items.',
    ],
    'multiple_of' => 'The :attribute must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute format is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'password' => 'The password is incorrect.',
    'present' => 'The :attribute field must be present.',
    'regex' => 'The :attribute format is invalid.',
    'required' => 'The :attribute field is required.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'prohibited' => 'The :attribute field is prohibited.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'numeric' => 'The :attribute must be :size.',
        'file' => 'The :attribute must be :size kilobytes.',
        'string' => 'The :attribute must be :size characters.',
        'array' => 'The :attribute must contain :size items.',
    ],
    'starts_with' => 'The :attribute must start with one of the following: :values.',
    'string' => 'The :attribute must be a string.',
    'timezone' => 'The :attribute must be a valid zone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'url' => 'The :attribute format is invalid.',
    'uuid' => 'The :attribute must be a valid UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Nombres de los campos
    |--------------------------------------------------------------------------
    |
    | Con esto, «El campo hardware_device_id es obligatorio» pasa a ser
    | «El campo dispositivo es obligatorio», en los dos idiomas y para TODAS
    | las reglas, sin escribir un mensaje por regla y por campo.
    |
    | Antes cada FormRequest traía su propio `messages()` a mano: 98 cadenas
    | repartidas por 19 ficheros, la mitad sin tildes, que sólo cubrían las
    | reglas que a alguien se le ocurrieron y sólo en español.
    |
    */
    'attributes' => [
        'abilities' => 'token abilities',
        'abilities.*' => 'each token ability',
        'altitude' => 'altitude',
        'amperage' => 'current',
        'attributes' => 'extra fields',
        'attributes.*' => 'each extra field',
        'battery_current' => 'battery current',
        'battery_level' => 'battery level',
        'battery_percentage' => 'battery level',
        'battery_power' => 'battery power',
        'battery_temperature' => 'battery temperature',
        'battery_type' => 'battery type',
        'battery_voltage' => 'battery voltage',
        'charging_status' => 'charging status',
        'charging_status_label' => 'charging status label',
        'clicks_average' => 'average clicks',
        'clicks_left' => 'left clicks',
        'clicks_middle' => 'middle clicks',
        'clicks_right' => 'right clicks',
        'contactme' => 'contact permission',
        'cpu' => 'CPU usage',
        'data' => 'data',
        'data.*' => 'each record',
        'data.*.altitude' => 'altitude of the record',
        'data.*.flight' => 'flight of the record',
        'data.*.icao' => 'ICAO code of the record',
        'data.*.lat' => 'latitude of the record',
        'data.*.lon' => 'longitude of the record',
        'data.*.messages' => 'messages of the record',
        'data.*.seen' => 'seen of the record',
        'data.*.seen_pos' => 'position seen of the record',
        'data.*.speed' => 'speed of the record',
        'data.*.squawk' => 'squawk of the record',
        'data.*.track' => 'track of the record',
        'date' => 'date',
        'day_battery_voltage_max' => 'maximum battery voltage of the day',
        'day_battery_voltage_min' => 'minimum battery voltage of the day',
        'day_charging_amp_hours' => 'charged amp hours of the day',
        'day_charging_current_max' => 'maximum charging current of the day',
        'day_charging_power_max' => 'maximum charging power of the day',
        'day_discharging_amp_hours' => 'discharged amp hours of the day',
        'day_discharging_current_max' => 'maximum discharging current of the day',
        'day_discharging_power_max' => 'maximum discharging power of the day',
        'day_power_consumption_wh' => 'consumed energy of the day',
        'day_power_generation_wh' => 'generated energy of the day',
        'delta_seconds' => 'seconds covered by the average',
        'device_id' => 'device',
        'disk' => 'disk usage',
        'duration' => 'measurement duration',
        'email' => 'email',
        'end_at' => 'end time',
        'expires_at' => 'expiry date',
        'extra' => 'extra data',
        'extra.*' => 'each extra value',
        'flight' => 'flight',
        'full_water_tank' => 'full water tank',
        'g-recaptcha-response' => 'security check',
        'hardware' => 'hardware model',
        'hardware_device_id' => 'device',
        'humidity' => 'humidity',
        'icao' => 'ICAO code',
        'ip_local' => 'local IP',
        'ip_public' => 'public IP',
        'lat' => 'latitude',
        'light_brightness' => 'street light brightness',
        'light_status' => 'street light status',
        'load_current' => 'load current',
        'load_fan' => 'load fan',
        'load_power' => 'load power',
        'load_voltage' => 'load voltage',
        'location_type' => 'location type',
        'lon' => 'longitude',
        'message' => 'message',
        'messages' => 'messages',
        'name' => 'name',
        'nickname' => 'nickname',
        'nominal_battery_capacity' => 'nominal battery capacity',
        'password' => 'password',
        'plant_id' => 'plant',
        'platform_id' => 'platform',
        'power' => 'power',
        'pressure' => 'pressure',
        'privacity' => 'privacy policy acceptance',
        'pulsation_average' => 'average keystrokes',
        'pulsations' => 'keystrokes',
        'pulsations_special_keys' => 'special key strokes',
        'read_at' => 'reading time',
        'readings' => 'readings',
        'readings.*' => 'each reading',
        'readings.*.amperage' => 'reading current',
        'readings.*.battery_percentage' => 'reading battery level',
        'readings.*.battery_voltage' => 'reading battery voltage',
        'readings.*.duration' => 'reading duration',
        'readings.*.energy_wh' => 'reading energy',
        'readings.*.fan' => 'reading fan',
        'readings.*.pos' => 'reading channel',
        'readings.*.read_at' => 'reading timestamp',
        'readings.*.temperature' => 'reading temperature',
        'readings.*.voltage' => 'reading voltage',
        'score' => 'score',
        'seen' => 'seen',
        'seen_pos' => 'position seen',
        'sensors' => 'sensors',
        'sensors.*' => 'each sensor',
        'serial_number' => 'serial number',
        'soil_humidity' => 'soil humidity',
        'soil_humidity_raw' => 'raw soil humidity',
        'speed' => 'speed',
        'squawk' => 'squawk',
        'start_at' => 'start time',
        'subject' => 'subject',
        'system_intensity' => 'system current',
        'system_voltage' => 'system voltage',
        'temp' => 'temperature',
        'temperature' => 'temperature',
        'total_battery_full_charges' => 'battery full charges',
        'total_battery_over_discharges' => 'battery over-discharges',
        'total_charging_amp_hours' => 'total charged amp hours',
        'total_clicks' => 'total clicks',
        'total_discharging_amp_hours' => 'total discharged amp hours',
        'total_operating_days' => 'operating days',
        'total_power_consumption_wh' => 'total consumed energy',
        'total_power_generation_wh' => 'total generated energy',
        'track' => 'track',
        'uptime' => 'uptime',
        'user_id' => 'user',
        'uv' => 'UV index',
        'vaporizer_enabled' => 'vaporizer enabled',
        'version' => 'firmware version',
        'voltage' => 'voltage',
        'waterpump_enabled' => 'water pump enabled',
        'weekday' => 'weekday',
    ],

];
