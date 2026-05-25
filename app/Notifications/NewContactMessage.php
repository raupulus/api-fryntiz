<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewContactMessage extends Notification
{
    use Queueable;

    public function __construct(public array $data) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nuevo mensaje de contacto')
            ->line('Has recibido un nuevo mensaje de contacto de ' . ($this->data['name'] ?? 'Anonimo'))
            ->line('Asunto: ' . ($this->data['subject'] ?? 'Sin asunto'))
            ->action('Ver en el panel', url('/admin'));
    }

    public function toArray(object $notifiable): array
    {
        return $this->data;
    }
}
