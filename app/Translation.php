<?php

namespace App;

use function array_combine;
use Illuminate\Database\Eloquent\Model;
use function mt_rand;
use function range;
use function strlen;

class Translation extends MinModel
{
    protected $table = 'translations';

    public static $rules = [
        'token' => 'required|min:13',
    ];

    /**
     * Almacena los carácteres permitidos para generar un token.
     *
     * @var array|false
     */
    protected $charsToken = [];


    public function __construct(array $attributes = [])
    {
        $this->charsToken = array_combine(range('a', 'z'), range(0, 9));

        parent::__construct($attributes);
    }


    public function random_id_gen($length)
    {
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $max = strlen($characters) - 1;
        $string = '';

        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[mt_rand(0, $max)];
        }

        return $string;
    }

    function map_basing($number, $from_characters, $to_characters)
    {
        if (strlen($from_characters) != strlen($to_characters)) {
            // ERROR!
        }
        $mapped = '';

        foreach ($number as $ch) {
        $pos = strpos($from_characters, $ch);
        if ($pos !== false) {
            $mapped .= $to_characters[$pos];
        } else { // ERROR!
        }
    }

        return $mapped;
    }

    public function next_id($last_id)
    {
        $my_characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $std_characters = '0123456789abcdefghijklmnopqrstuv'; // Map from your basing to the standard basing.
        $mapped = map_basing($last_id, $my_characters, $std_characters); // Convert to base 10 integer and increment.
        $intval = base_convert($mapped, strlen($my_characters), 10); $intval++; // Convert to standard basing, then to our custom basing.
        $newval_std = base_convert($intval, 10, strlen($my_characters));
        $newval = map_basing($newval_std, $std_characters, $my_characters);

        return $newval;
    }
}
