<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepenseAvecLignesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Gérer avec middleware auth
    }

    public function rules(): array
    {
        return [
            // Infos générales de la dépense
            'chantier_id' => 'required|exists:chantiers,id',
            'numero_facture' => 'nullable|string|max:255',
            'fournisseur' => 'nullable|string|max:255',
            'date_facture' => 'nullable|date',
            'observations' => 'nullable|string',
            'statut_paiement' => 'nullable|in:paye,non_paye',
            'statut_livraison' => 'nullable|in:livre,non_livre',
            
            // Lignes de dépense (minimum 1 ligne)
            'lignes' => 'required|array|min:1',
            'lignes.*.designation' => 'required|string|max:255',
            'lignes.*.categorie_depense_id' => 'nullable|exists:categories_depenses,id',
            'lignes.*.unite_mesure_id' => 'nullable|exists:unites_mesure,id',
            'lignes.*.quantite' => 'required|numeric|min:0.01',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
            'lignes.*.observations' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'chantier_id.required' => 'Le chantier est obligatoire',
            'chantier_id.exists' => 'Le chantier sélectionné n\'existe pas',
            'lignes.required' => 'Au moins une ligne de dépense est requise',
            'lignes.min' => 'Au moins une ligne de dépense est requise',
            'lignes.*.designation.required' => 'La désignation de chaque article est obligatoire',
            'lignes.*.quantite.required' => 'La quantité est obligatoire',
            'lignes.*.quantite.min' => 'La quantité doit être supérieure à 0',
            'lignes.*.prix_unitaire.required' => 'Le prix unitaire est obligatoire',
            'lignes.*.prix_unitaire.min' => 'Le prix unitaire doit être positif',
        ];
    }
}
