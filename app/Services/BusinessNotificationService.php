<?php

namespace App\Services;

use App\Jobs\SendNotificationMail;
use App\Models\Chantier;
use App\Models\Depense;
use App\Models\User;
use App\Notifications\ChantierAffecteNotification;
use App\Notifications\DepenseSoumiseNotification;
use Illuminate\Support\Facades\Notification;

class BusinessNotificationService
{
    public function expenseSubmitted(Depense $depense, User $auteur, bool $resoumission = false): void
    {
        $destinataires = User::query()
            ->where('is_active', true)
            ->whereIn('role', ['administrateur', 'directeur', 'comptable'])
            ->whereKeyNot($auteur->id)
            ->get();

        Notification::send($destinataires, new DepenseSoumiseNotification(
            $depense->loadMissing('chantier'),
            $auteur,
            $resoumission,
            ['database'],
        ));

        foreach ($destinataires as $destinataire) {
            SendNotificationMail::dispatch($destinataire, new DepenseSoumiseNotification(
                $depense,
                $auteur,
                $resoumission,
                ['mail'],
            ));
        }
    }

    public function chantierAssigned(Chantier $chantier, User $assignee, User $auteur): void
    {
        $assignee->notify(new ChantierAffecteNotification($chantier, $auteur, ['database']));

        SendNotificationMail::dispatch(
            $assignee,
            new ChantierAffecteNotification($chantier, $auteur, ['mail']),
        );
    }
}
