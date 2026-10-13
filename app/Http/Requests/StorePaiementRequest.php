<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaiementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('recordPayment', $this->route('depense')) ?? false;
    }

    public function rules(): array
    {
        return [
            'montant' => 'required|numeric|decimal:0,2|min:0.01|max:9999999999999.99',
            'date_paiement' => 'required|date|before_or_equal:today',
            'mode' => ['required', Rule::in(['virement', 'especes', 'cheque', 'mobile_money', 'autre'])],
            'reference' => 'required|string|max:255',
            'reprise_historique' => 'sometimes|boolean',
        ];
    }
}
