<?php

namespace App\Notifications;

use App\Models\Depense;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepenseSoumiseNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Depense $depense,
        private readonly User $auteur,
        private readonly bool $resoumission = false,
        private readonly array $channels = ['database', 'mail'],
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $action = $this->resoumission ? 'resoumise' : 'soumise';

        return (new MailMessage)
            ->subject('Nouvelle dépense '.$action.' - '.$this->depense->libelle)
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line($this->auteur->name.' a '.$action.' une dépense sur le chantier '.$this->depense->chantier->nom.'.')
            ->line('Montant : '.number_format((float) $this->depense->prix_total, 0, ',', ' ').' FCFA')
            ->action('Consulter la dépense', route('depenses.show', $this->depense))
            ->line('Cette alerte est également disponible dans la cloche de l’application.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'depense_soumise',
            'title' => $this->resoumission ? 'Dépense soumise à nouveau' : 'Nouvelle dépense soumise',
            'message' => $this->auteur->name.' a soumis '.$this->depense->libelle.' pour '.number_format((float) $this->depense->prix_total, 0, ',', ' ').' FCFA.',
            'url' => route('depenses.show', $this->depense, false),
            'depense_id' => $this->depense->id,
            'chantier_id' => $this->depense->chantier_id,
        ];
    }
}
