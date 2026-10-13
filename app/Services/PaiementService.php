<?php

namespace App\Services;

use App\Models\Chantier;
use App\Models\Depense;
use App\Models\Paiement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PaiementService
{
    public function __construct(private DepenseService $depenses) {}

    public function record(Depense $depense, array $data, User $user): Paiement
    {
        return DB::transaction(function () use ($depense, $data, $user) {
            Chantier::query()->whereKey($depense->chantier_id)->lockForUpdate()->firstOrFail();
            $locked = Depense::query()->lockForUpdate()->findOrFail($depense->id);
            Gate::forUser($user)->authorize('recordPayment', $locked);
            $this->depenses->ensureInitialHistory($locked);

            $paid = $this->paidAmount($locked);
            $remaining = bcsub((string) $locked->prix_total, $paid, 2);
            $reference = trim((string) $data['reference']);
            if ($reference === '') {
                throw ValidationException::withMessages(['reference' => 'La référence du paiement est obligatoire.']);
            }
            $legacy = $locked->statut_paiement === 'paye' && ! $locked->paiements()->exists();
            if ($legacy && ! ($data['reprise_historique'] ?? false)) {
                throw ValidationException::withMessages(['reprise_historique' => 'Cette facture était déjà marquée payée avant le registre. Rapprochez son paiement historique avec sa date et sa référence.']);
            }
            if (! $legacy && ($data['reprise_historique'] ?? false)) {
                throw ValidationException::withMessages(['reprise_historique' => 'La reprise historique ne s’applique pas à cette facture.']);
            }
            if (bccomp((string) $data['montant'], '0', 2) <= 0 || bccomp((string) $data['montant'], $remaining, 2) > 0 || ($legacy && bccomp((string) $data['montant'], $remaining, 2) !== 0)) {
                throw ValidationException::withMessages(['montant' => 'Le montant doit être positif et ne pas dépasser le reste à payer. Une reprise historique doit couvrir la facture entière.']);
            }
            if ($locked->paiements()->where('reference', $reference)->whereNull('annule_le')->exists()) {
                throw ValidationException::withMessages(['reference' => 'Cette référence de paiement est déjà utilisée pour cette facture.']);
            }

            $paiement = $locked->paiements()->create([
                'montant' => $data['montant'],
                'date_paiement' => $data['date_paiement'],
                'mode' => $data['mode'],
                'reference' => $reference,
                'enregistre_par' => $user->id,
                'enregistre_par_nom' => $user->name,
                'reprise_historique' => $legacy,
            ]);
            $this->syncStatus($locked);
            $this->depenses->recordHistory($locked, $legacy ? 'reprise_paiement' : 'paiement', $user, 'validee');

            return $paiement;
        });
    }

    public function cancel(Paiement $paiement, string $reason, User $user): Paiement
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['motif_annulation' => 'Indiquez le motif de l’annulation.']);
        }

        return DB::transaction(function () use ($paiement, $reason, $user) {
            $depense = $paiement->depense;
            Chantier::query()->whereKey($depense->chantier_id)->lockForUpdate()->firstOrFail();
            $locked = Depense::query()->lockForUpdate()->findOrFail($depense->id);
            Gate::forUser($user)->authorize('cancelPayment', $locked);
            $this->depenses->ensureInitialHistory($locked);
            $lockedPayment = Paiement::query()->where('depense_id', $locked->id)->lockForUpdate()->findOrFail($paiement->id);
            if ($lockedPayment->annule_le) {
                throw ValidationException::withMessages(['paiement' => 'Ce paiement est déjà annulé.']);
            }
            $lockedPayment->update([
                'annule_le' => now(),
                'annule_par' => $user->id,
                'annule_par_nom' => $user->name,
                'motif_annulation' => trim($reason),
            ]);
            $this->syncStatus($locked);
            $this->depenses->recordHistory($locked, 'annulation_paiement', $user, 'validee', $reason);

            return $lockedPayment->fresh();
        });
    }

    public function paidAmount(Depense $depense): string
    {
        return (string) ($depense->paiements()->whereNull('annule_le')->sum('montant') ?: '0.00');
    }

    private function syncStatus(Depense $depense): void
    {
        $paid = $this->paidAmount($depense);
        $depense->update(['statut_paiement' => bccomp($paid, (string) $depense->prix_total, 2) >= 0 ? 'paye' : 'non_paye']);
    }
}
