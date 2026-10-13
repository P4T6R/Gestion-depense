<?php

namespace App\Services;

use App\Models\Chantier;
use App\Support\Amount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ChantierFinancialService
{
    /** @return array<int, array<string, string|int>> */
    public function forChantiers(Collection $chantiers): array
    {
        $ids = $chantiers->pluck('id')->all();
        if ($ids === []) {
            return [];
        }

        $invoices = DB::table('depenses')->whereNull('deleted_at')->whereIn('chantier_id', $ids)
            ->selectRaw("chantier_id,
                COALESCE(SUM(CASE WHEN statut_validation IN ('en_attente', 'acceptee') THEN prix_total ELSE 0 END), 0) AS en_attente,
                COALESCE(SUM(CASE WHEN statut_validation = 'validee' THEN prix_total ELSE 0 END), 0) AS validees")
            ->groupBy('chantier_id')->get()->keyBy('chantier_id');

        $payments = DB::table('paiements')->join('depenses', 'depenses.id', '=', 'paiements.depense_id')
            ->whereNull('paiements.annule_le')->whereNull('depenses.deleted_at')->whereIn('depenses.chantier_id', $ids)
            ->selectRaw('depenses.chantier_id, COALESCE(SUM(paiements.montant), 0) AS paye')
            ->groupBy('depenses.chantier_id')->get()->keyBy('chantier_id');

        $legacy = DB::table('depenses')->whereNull('deleted_at')->whereIn('chantier_id', $ids)
            ->where('statut_validation', 'validee')->where('statut_paiement', 'paye')
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('paiements')->whereColumn('paiements.depense_id', 'depenses.id'))
            ->selectRaw('chantier_id, COUNT(*) AS nombre, COALESCE(SUM(prix_total), 0) AS montant')
            ->groupBy('chantier_id')->get()->keyBy('chantier_id');

        $result = [];
        foreach ($chantiers as $chantier) {
            $id = $chantier->id;
            $allocated = (string) $chantier->budget_alloue;
            $pending = (string) ($invoices[$id]->en_attente ?? '0.00');
            $validated = (string) ($invoices[$id]->validees ?? '0.00');
            $paid = (string) ($payments[$id]->paye ?? '0.00');
            $result[$id] = [
                'budget_alloue' => $allocated,
                'factures_en_attente' => $pending,
                'depenses_validees' => $validated,
                'montant_paye' => $paid,
                'reste_a_payer' => bcsub($validated, $paid, 2),
                'solde_disponible' => bcsub($allocated, $validated, 2),
                'historique_a_rapprocher' => (string) ($legacy[$id]->montant ?? '0.00'),
                'factures_historiques_a_rapprocher' => (int) ($legacy[$id]->nombre ?? 0),
            ];
        }

        return $result;
    }

    public function forChantier(Chantier $chantier): array
    {
        return $this->forChantiers(collect([$chantier]))[$chantier->id];
    }

    public function totals(Collection $chantiers): array
    {
        $summaries = $this->forChantiers($chantiers);
        $fields = ['budget_alloue', 'factures_en_attente', 'depenses_validees', 'montant_paye', 'reste_a_payer', 'solde_disponible', 'historique_a_rapprocher'];
        $result = [];
        foreach ($fields as $field) {
            $result[$field] = Amount::sum(array_column($summaries, $field));
        }
        $result['factures_historiques_a_rapprocher'] = array_sum(array_column($summaries, 'factures_historiques_a_rapprocher'));

        return $result;
    }
}
