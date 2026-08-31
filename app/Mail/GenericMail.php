<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use function array_merge;
use function config;

class GenericMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;

    public $view;

    public $to;

    public $from;

    public $subject;

    /**
     * Create a new message instance. Generic use.
     *
     * Para adjuntar un archivo recibe en data un elemento con key attached
     * que será un array conteniendo:
     * data: contenido del archivo
     * name: nombre del archivo al ser adjuntado
     * mime: tipo mime del archivo
     *
     * @param  null  $data
     */
    public function __construct($config, $data = null)
    {
        $config = array_merge([
            'to' => config('mail.from.address'),
            'from' => config('mail.from.address'),
            'subject' => 'Mensaje desde '.config('app.name'),
            'view' => 'mail.mail_generic',  // Vista que procesará el email
        ], $config);

        if ($data && isset($data['attached'])) {
            $this->attachData(
                $data['attached']['data'],
                $data['attached']['name'],
                [
                    'mime' => $data['attached']['mime'],
                ]
            );
        }

        $this->data = $data;
        $this->subject = $config['subject'];
        $this->view = $config['view'];
        $this->to = $config['to'];
        $this->from = $config['from'];
    }

    /**
     * Email Genérico para complementar o uso rápido/general al enviar email.
     *
     * @return GenericMail
     */
    public function build()
    {
        return $this->view($this->view, $this->data)
            ->to($this->to, $this->from)
            ->subject($this->subject)
            ->from(config('mail.from.address'), config('mail.from.name'));
    }
}
