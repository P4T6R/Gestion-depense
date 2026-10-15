<?php

namespace App\Services;

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
            try {
                $destinataire->notify(new DepenseSoumiseNotification(
                    $depense,
                    $auteur,
                    $resoumission,
                    ['mail'],
                ));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }

    public function chantierAssigned(Chantier $chantier, User $assignee, User $auteur): void
    {
        $assignee->notify(new ChantierAffecteNotification($chantier, $auteur, ['database']));

        try {
            $assignee->notify(new ChantierAffecteNotification($chantier, $auteur, ['mail']));
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
