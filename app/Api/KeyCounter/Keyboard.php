<?php

namespace App\Keycounter;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use function count;

class Keyboard extends KeyCounter
{
    protected $table = 'keycounter_keyboard';
}
