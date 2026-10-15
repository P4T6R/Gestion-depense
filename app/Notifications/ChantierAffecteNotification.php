<?php

namespace App\Notifications;

use App\Models\Chantier;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChantierAffecteNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Chantier $chantier,
        private readonly User $auteur,
        private readonly array $channels = ['database', 'mail'],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouveau chantier attribué - '.$this->chantier->nom)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->auteur->name.' vous a attribué le chantier '.$this->chantier->nom.'.')
            ->line('Lieu : '.($this->chantier->lieu ?: 'Non renseigné'))
            ->action('Consulter le chantier', route('chantiers.show', $this->chantier))
            ->line('Cette alerte est également disponible dans la cloche de l’application.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'chantier_affecte',
            'title' => 'Nouveau chantier attribué',
            'message' => $this->auteur->name.' vous a attribué le chantier '.$this->chantier->nom.'.',
            'url' => route('chantiers.show', $this->chantier, false),
            'chantier_id' => $this->chantier->id,
        ];
    }
}
