<?php

namespace App\Http\Requests;

use App\Models\Depense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepenseRequest extends FormRequest
{
    use ValidatesDepenseAmounts;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Depense::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->is('api/*')) {
            $this->merge([
                'statut_paiement' => $this->input('statut_paiement', 'non_paye'),
                'statut_livraison' => $this->input('statut_livraison', 'non_livre'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'chantier_id' => ['required', 'integer', Rule::exists('chantiers', 'id')->whereNull('deleted_at')],
            'nom_produit' => 'required|string|max:255',
            'quantite' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'prix_unitaire' => 'required|numeric|decimal:0,2|min:0|max:9999999999999.99',
            'unite_id' => ['required', 'integer', Rule::exists('unites_mesure', 'id')->where('is_active', true)],
            'categorie_id' => ['required', 'integer', Rule::exists('categories_depenses', 'id')->where('is_active', true)->where('type', $this->user()->isDirecteur() ? 'administrative' : 'chantier')],
            'observations' => 'nullable|string|max:10000',
            'id_produit' => 'nullable|string|max:100',
            'statut_paiement' => 'required|in:paye,non_paye',
            'statut_livraison' => 'required|in:livre,non_livre',
        ] + self::attachmentRules();
    }

    public static function attachmentRules(): array
    {
        return [
            'justificatif' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'justificatifs' => 'nullable|array|max:20',
            'justificatifs.*' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ];
    }
}
