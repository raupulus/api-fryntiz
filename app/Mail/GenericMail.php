<?php

namespace App\Mail;

use function array_merge;
use function config;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GenericMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data, $view, $to, $from, $subject;

    /**
     * Create a new message instance. Generic use.
     *
     * Para adjuntar un archivo recibe en data un elemento con key attached
     * que será un array conteniendo:
     * data: contenido del archivo
     * name: nombre del archivo al ser adjuntado
     * mime: tipo mime del archivo
     *
     * @param      $config
     * @param null $data
     */
    public function __construct($config, $data = null)
    {
        $config = array_merge([
            'to' => config('mail.from.address'),
            'from' => config('mail.from.address'),
            'subject' => 'Mensaje desde ' . config('app.name'),
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
     * @return \App\Mail\GenericMail
     */
    public function build()
    {
        return $this->view($this->view, $this->data)
            ->to($this->to, $this->from)
            ->subject($this->subject)
            ->from(config('mail.from.address'), config('mail.from.name'));
    }
}
