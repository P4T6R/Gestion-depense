<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MailConfigurationTestNotification extends Notification
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Test des notifications - Gestion Dépense')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('La file d’attente des courriels de Gestion Dépense fonctionne pour votre rôle : '.$notifiable->role.'.')
            ->line('Aucune action n’est requise.');
    }
}
