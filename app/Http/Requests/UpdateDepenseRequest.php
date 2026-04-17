<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDepenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $depense = $this->route('depense');
        $user = $this->user();
        
        // Seul le créateur peut modifier sa dépense
        if ($depense->user_id !== $user->id) {
            return false;
        }
        
        // Et seulement si elle est encore en attente
        if ($depense->statut_validation !== 'en_attente') {
            return false;
        }
        
        return in_array($user->role, ['chef_chantier', 'directeur']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'chantier_id' => 'required|exists:chantiers,id',
            'nom_produit' => 'required|string|max:255',
            'quantite' => 'required|numeric|min:0.01',
            'prix_unitaire' => 'required|numeric|min:0.01',
            'unite_id' => 'required|exists:unites_mesure,id',
            'categorie_id' => 'required|exists:categories_depenses,id',
            'observations' => 'nullable|string|max:1000',
            'id_produit' => 'nullable|string|max:100',
            'justificatif' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'chantier_id.required' => 'Veuillez sélectionner un chantier.',
            'nom_produit.required' => 'Le nom du produit est obligatoire.',
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.min' => 'La quantité doit être supérieure à 0.',
            'prix_unitaire.required' => 'Le prix unitaire est obligatoire.',
            'prix_unitaire.min' => 'Le prix unitaire doit être supérieur à 0.',
            'unite_id.required' => 'Veuillez sélectionner une unité de mesure.',
            'categorie_id.required' => 'Veuillez sélectionner une catégorie.',
            'justificatif.mimes' => 'Le justificatif doit être au format JPEG, JPG, PNG ou PDF.',
            'justificatif.max' => 'Le justificatif ne peut pas dépasser 5 Mo.',
        ];
    }
}
