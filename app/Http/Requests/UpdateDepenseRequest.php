<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDepenseRequest extends FormRequest
{
    use ValidatesDepenseAmounts;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('depense')) ?? false;
    }

    public function rules(): array
    {
        $rules = [
            'chantier_id' => ['sometimes', 'required', 'integer', Rule::exists('chantiers', 'id')->whereNull('deleted_at')],
            'numero_facture' => 'sometimes|nullable|string|max:255',
            'fournisseur' => 'sometimes|nullable|string|max:255',
            'date_facture' => 'sometimes|nullable|date',
            'observations' => 'sometimes|nullable|string|max:10000',
            'statut_paiement' => 'sometimes|in:paye,non_paye',
            'statut_livraison' => 'sometimes|in:livre,non_livre',
        ] + StoreDepenseRequest::attachmentRules();

        if ($this->route('depense')->lignes()->exists() || $this->has('lignes')) {
            $lineRules = StoreDepenseAvecLignesRequest::lineRules($this->user());
            if (! $this->has('lignes')) {
                $lineRules['lignes'] = 'sometimes|array|min:1|max:100';
            }

            return $rules + $lineRules + [
                'quantite' => 'prohibited', 'prix_unitaire' => 'prohibited', 'nom_produit' => 'prohibited',
                'categorie_id' => 'prohibited', 'unite_id' => 'prohibited',
            ];
        }

        return $rules + [
            'nom_produit' => 'sometimes|required|string|max:255',
            'quantite' => 'sometimes|required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'prix_unitaire' => 'sometimes|required|numeric|decimal:0,2|min:0|max:9999999999999.99',
            'unite_id' => ['sometimes', 'required', 'integer', Rule::exists('unites_mesure', 'id')->where('is_active', true)],
            'categorie_id' => ['sometimes', 'required', 'integer', Rule::exists('categories_depenses', 'id')->where('is_active', true)->where('type', $this->user()->isDirecteur() ? 'administrative' : 'chantier')],
            'id_produit' => 'sometimes|nullable|string|max:100',
        ];
    }
}
