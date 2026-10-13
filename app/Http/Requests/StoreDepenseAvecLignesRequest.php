<?php

namespace App\Http\Requests;

use App\Models\Depense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepenseAvecLignesRequest extends FormRequest
{
    use ValidatesDepenseAmounts;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Depense::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'chantier_id' => ['required', 'integer', Rule::exists('chantiers', 'id')->whereNull('deleted_at')],
            'numero_facture' => 'nullable|string|max:255',
            'fournisseur' => 'nullable|string|max:255',
            'date_facture' => 'nullable|date',
            'observations' => 'nullable|string|max:10000',
            'statut_paiement' => 'nullable|in:paye,non_paye',
            'statut_livraison' => 'nullable|in:livre,non_livre',
        ] + self::lineRules($this->user()) + StoreDepenseRequest::attachmentRules();
    }

    public static function lineRules($user): array
    {
        return [
            'lignes' => 'required|array|min:1|max:100',
            'lignes.*' => 'array:designation,categorie_depense_id,unite_mesure_id,quantite,prix_unitaire,observations',
            'lignes.*.designation' => 'required|string|max:255',
            'lignes.*.categorie_depense_id' => ['nullable', 'integer', Rule::exists('categories_depenses', 'id')->where('is_active', true)->where('type', $user->isDirecteur() ? 'administrative' : 'chantier')],
            'lignes.*.unite_mesure_id' => ['nullable', 'integer', Rule::exists('unites_mesure', 'id')->where('is_active', true)],
            'lignes.*.quantite' => 'required|numeric|decimal:0,2|min:0.01|max:99999999.99',
            'lignes.*.prix_unitaire' => 'required|numeric|decimal:0,2|min:0|max:9999999999999.99',
            'lignes.*.observations' => 'nullable|string|max:10000',
        ];
    }
}
