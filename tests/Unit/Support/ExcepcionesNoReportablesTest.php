<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Exceptions\JsonAuthorizationException;
use App\Exceptions\JsonValidationException;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Un 422 y un 403 no van al log.
 *
 * Son respuestas del contrato: el cliente manda algo mal y se le dice. No son
 * fallos del servidor y no tienen que dejar una traza de setenta líneas.
 *
 * Las dos excepciones llevaban un `report(): bool { return false; }` con un
 * comentario diciendo que no se reportaban, y hacía lo contrario: el handler de
 * Laravel sólo deja de reportar cuando `report()` devuelve algo **distinto** de
 * `false`. En producción cada petición mal formada llenaba el log.
 */
class ExcepcionesNoReportablesTest extends TestCase
{
    #[Test]
    public function un_error_de_validacion_no_se_registra(): void
    {
        $validator = Validator::make([], ['campo' => 'required']);

        $this->assertFalse(
            app(ExceptionHandler::class)->shouldReport(new JsonValidationException($validator)),
            'Un 422 de validación se está escribiendo en el log.'
        );
    }

    #[Test]
    public function una_denegacion_no_se_registra(): void
    {
        $this->assertFalse(
            app(ExceptionHandler::class)->shouldReport(new JsonAuthorizationException),
            'Un 403 se está escribiendo en el log.'
        );
    }
}
