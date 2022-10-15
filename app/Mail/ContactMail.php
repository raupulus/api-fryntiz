<?php

namespace App\Mail;

use function config;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Class ContactMail
 *
 * @package App\Mail
 */
class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data, $subject;

    /**
     * ContactMail constructor.
     *
     * Para adjuntar un archivo recibe en data un elemento con key attached
     * que será un array conteniendo:
     * data: contenido del archivo
     * name: nombre del archivo al ser adjuntado
     * mime: tipo mime del archivo
     *
     * @param $data
     */
    public function __construct($data)
    {
        /*
        if ($data && isset($data['attached'])) {
            $this->attachData(
                $data['attached']['data'],
                $data['attached']['name'],
                [
                    'mime' => $data['attached']['mime'],
                ]
            );
        }
        */

        $this->data = $data;
        $this->subject = $data['subject'];
        //$this->from = $data['email'];
        //$this->to = [$this->to, $this->from];
    }

    /**
     * Email para el formulario de contacto.
     *
     * @return \App\Mail\ContactMail
     */
    public function build()
    {
        return $this->view('mail.mail_contact', ['data' => $this->data])
            ->to($this->data['email'], config('mail.from.address'))
            ->subject($this->subject)
            ->from(config('mail.from.address'), config('mail.from.name'));
    }
}
