<?php

namespace App\Http\Requests;

trait ValidatesDepenseAmounts
{
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $depense = $this->route('depense');
            $lines = $this->input('lignes');
            $total = is_array($lines)
                ? \App\Support\Amount::sum(array_map(fn ($line) => \App\Support\Amount::multiply((string) $line['quantite'], (string) $line['prix_unitaire']), $lines))
                : \App\Support\Amount::multiply((string) $this->input('quantite', $depense?->quantite ?? 0), (string) $this->input('prix_unitaire', $depense?->prix_unitaire ?? 0));
            if (bccomp($total, '9999999999999.99', 2) > 0) {
                $validator->errors()->add('prix_total', 'Le montant dépasse la limite autorisée.');
            }
        });
    }
}
