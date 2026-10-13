<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaiementRequest;
use App\Models\Depense;
use App\Models\Paiement;
use App\Services\PaiementService;
use Illuminate\Http\Request;

class PaiementController extends Controller
{
    public function __construct(private PaiementService $service) {}

    public function store(StorePaiementRequest $request, Depense $depense)
    {
        $this->service->record($depense, $request->validated(), $request->user());

        return to_route('depenses.show', $depense)->with('success', 'Paiement enregistré.');
    }

    public function cancel(Request $request, Depense $depense, Paiement $paiement)
    {
        $this->authorize('cancelPayment', $depense);
        abort_unless($paiement->depense_id === $depense->id, 404);
        $data = $request->validate(['motif_annulation' => 'required|string|min:10|max:10000']);
        $this->service->cancel($paiement, $data['motif_annulation'], $request->user());

        return to_route('depenses.show', $depense)->with('success', 'Paiement annulé.');
    }
}
