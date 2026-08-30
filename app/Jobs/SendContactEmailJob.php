<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\ContactMail;
use App\Models\Email;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Reenvía al buzón un mensaje de contacto que ha pasado el filtro.
 *
 * Va en cola porque el visitante no tiene que esperar al servidor de correo, y
 * sobre todo porque si el SMTP está caído el mensaje ya está guardado: se
 * reintenta luego en vez de devolverle un error a alguien que ha escrito bien.
 *
 * El resultado se anota en la propia fila (`sent_at`, `attempts`, `error_*`),
 * que es lo que mira el panel.
 */
class SendContactEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public function __construct(public readonly int $emailId) {}

    public function handle(): void
    {
        $email = Email::query()->find($this->emailId);

        if (! $email || ! $email->send || $email->sent_at !== null) {
            return;
        }

        $destination = $email->user->email ?? config('mail.admin_address', config('mail.from.address'));

        if (empty($destination)) {
            Log::error('Contacto: no hay buzón de destino configurado', ['email_id' => $email->id]);

            return;
        }

        $email->increment('attempts');

        Mail::to($destination)->send(new ContactMail([
            'name' => $email->extraData()['name'] ?? null,
            'email' => $email->email,
            'subject' => $email->subject,
            'message' => $email->message,
        ]));

        $email->forceFill([
            'sent_at' => now(),
            'error_code' => null,
            'error_at' => null,
            'error_message' => null,
        ])->save();
    }

    /**
     * Si se agotan los reintentos, queda constancia en la fila.
     */
    public function failed(\Throwable $e): void
    {
        Email::query()->whereKey($this->emailId)->update([
            'error_code' => $e->getCode() === 0 ? null : (int) $e->getCode(),
            'error_at' => now(),
            'error_message' => mb_substr($e->getMessage(), 0, 1000),
        ]);
    }
}
