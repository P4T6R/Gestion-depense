<?php

namespace App\Services;

use Illuminate\Support\Collection;

class ExpenseReportingService
{
    /** Les lignes font foi pour les factures ; les anciennes dépenses restent mono-produit. */
    public function byCategory(Collection $expenses): Collection
    {
        $expenses->loadMissing('lignes.categorieDepense', 'categorie');
        $groups = [];
        foreach ($expenses as $expense) {
            $entries = $expense->lignes->isNotEmpty()
                ? $expense->lignes->map(fn ($line) => [$line->categorieDepense, $line->montant])
                : collect([[$expense->categorie, $expense->prix_total]]);
            foreach ($entries as [$category, $amount]) {
                $key = $category ? (string) $category->id : '';
                $groups[$key] ??= [
                    'categorie_id' => $category?->id, 'categorie' => $category,
                    'nom' => $category?->nom ?? 'Non classée', 'montant' => '0.00', 'ids' => [],
                ];
                $groups[$key]['montant'] = bcadd($groups[$key]['montant'], (string) $amount, 2);
                $groups[$key]['ids'][$expense->id] = true;
            }
        }

        return collect($groups)->map(function ($group) {
            $group['count'] = count($group['ids']);
            $group['total'] = $group['montant'];
            unset($group['ids']);

            return $group;
        });
    }
}
