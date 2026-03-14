<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['chef_chantier', 'directeur']);
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
            'chantier_id.exists' => 'Le chantier sélectionné n\'existe pas.',
            'nom_produit.required' => 'Le nom du produit est obligatoire.',
            'nom_produit.max' => 'Le nom du produit ne peut pas dépasser 255 caractères.',
            'quantite.required' => 'La quantité est obligatoire.',
            'quantite.numeric' => 'La quantité doit être un nombre.',
            'quantite.min' => 'La quantité doit être supérieure à 0.',
            'prix_unitaire.required' => 'Le prix unitaire est obligatoire.',
            'prix_unitaire.numeric' => 'Le prix unitaire doit être un nombre.',
            'prix_unitaire.min' => 'Le prix unitaire doit être supérieur à 0.',
            'unite_id.required' => 'Veuillez sélectionner une unité de mesure.',
            'unite_id.exists' => 'L\'unité sélectionnée n\'existe pas.',
            'categorie_id.required' => 'Veuillez sélectionner une catégorie.',
            'categorie_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'justificatif.file' => 'Le justificatif doit être un fichier.',
            'justificatif.mimes' => 'Le justificatif doit être au format JPEG, JPG, PNG ou PDF.',
            'justificatif.max' => 'Le justificatif ne peut pas dépasser 5 Mo.',
        ];
    }
}
