<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepenseAvecLignesRequest;
use App\Http\Requests\StoreDepenseRequest;
use App\Http\Requests\UpdateDepenseRequest;
use App\Models\Depense;
use App\Services\DepenseService;
use App\Services\PaiementService;
use Illuminate\Http\Request;

class DepenseController extends Controller
{
    public function __construct(private DepenseService $service) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', Depense::class);
        $query = Depense::visibleTo($request->user())->with([
            'chantier', 'utilisateur', 'validateur', 'justificatifs', 'categorie', 'unite',
            'lignes.categorieDepense', 'lignes.uniteMesure',
        ]);
        foreach (['chantier_id', 'statut_validation', 'categorie_id'] as $filter) {
            if ($request->filled($filter)) {
                if ($filter === 'categorie_id') {
                    $query->inCategory($request->input($filter));
                } else {
                    $query->where($filter, $request->input($filter));
                }
            }
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(StoreDepenseRequest $request)
    {
        return $this->createExpense($request);
    }

    public function storeAvecLignes(StoreDepenseAvecLignesRequest $request)
    {
        return $this->createExpense($request);
    }

    private function createExpense($request)
    {
        $data = $request->validated();
        abort_unless($request->user()->hasAccessToChantier($data['chantier_id']), 403, 'Accès refusé à ce chantier');
        $data['user_id'] = $request->user()->id;
        $files = $request->file('justificatifs', []);
        if ($request->hasFile('justificatif')) {
            $files[] = $request->file('justificatif');
        }
        $depense = $this->service->create($data, $request->user(), $files);

        return response()->json(['message' => 'Dépense créée avec succès', 'depense' => $depense], 201);
    }

    public function show(Request $request, Depense $depense, PaiementService $paiements)
    {
        $this->authorize('view', $depense);

        $depense->load([
            'chantier', 'utilisateur', 'validateur', 'justificatifs', 'categorie', 'unite',
            'lignes.categorieDepense', 'lignes.uniteMesure', 'historique', 'paiements',
        ]);
        $paid = $paiements->paidAmount($depense);
        $depense->setAttribute('montant_paye', $paid);
        $depense->setAttribute('reste_a_payer', bcsub((string) $depense->prix_total, $paid, 2));
        $depense->setAttribute('paiement_historique_a_rapprocher', $depense->statut_validation === 'validee' && $depense->statut_paiement === 'paye' && $depense->paiements->isEmpty());

        return response()->json($depense);
    }

    public function update(UpdateDepenseRequest $request, Depense $depense)
    {
        $wasRejected = $depense->statut_validation === 'rejetee';
        $data = $request->validated();
        abort_unless($request->user()->hasAccessToChantier($data['chantier_id'] ?? $depense->chantier_id), 403, 'Accès refusé à ce chantier');
        $files = $request->file('justificatifs', []);
        if ($request->hasFile('justificatif')) {
            $files[] = $request->file('justificatif');
        }

        return response()->json([
            'message' => $wasRejected ? 'Facture corrigée et soumise à nouveau '.($depense->circuit_validation_directe ? 'à la comptable' : 'au directeur') : 'Dépense mise à jour avec succès',
            'depense' => $this->service->update($depense, $data, $request->user(), $files),
        ]);
    }

    public function approve(Request $request, Depense $depense)
    {
        return response()->json([
            'message' => 'Dépense approuvée et envoyée au comptable',
            'depense' => $this->service->approve($depense, $request->user()),
        ]);
    }

    public function valider(Request $request, Depense $depense)
    {
        return response()->json([
            'message' => 'Dépense validée avec succès',
            'depense' => $this->service->validate($depense, $request->user()),
        ]);
    }

    public function rejeter(Request $request, Depense $depense)
    {
        $this->authorize('reject', $depense);
        $data = $request->validate(['motif_rejet' => 'required|string|min:10|max:10000']);

        return response()->json([
            'message' => 'Dépense rejetée',
            'depense' => $this->service->reject($depense, $data['motif_rejet'], $request->user()),
        ]);
    }

    public function destroy(Request $request, Depense $depense)
    {
        $this->service->delete($depense, auth()->user());

        return response()->json(['message' => 'Dépense supprimée avec succès']);
    }

    public function statistiques(Request $request)
    {
        $query = Depense::visibleTo($request->user());
        if ($request->filled('chantier_id')) {
            $query->where('chantier_id', $request->chantier_id);
        }
        $stats = ['total_depenses' => (clone $query)->count()];
        foreach (['en_attente', 'acceptee', 'validee', 'rejetee'] as $status) {
            $key = ['acceptee' => 'acceptees', 'validee' => 'validees', 'rejetee' => 'rejetees'][$status] ?? $status;
            $stats[$key] = (clone $query)->where('statut_validation', $status)->count();
        }
        $stats['montant_total_valide'] = (clone $query)->where('statut_validation', 'validee')->sum('prix_total');
        $stats['montant_en_attente'] = (clone $query)->where('statut_validation', 'en_attente')->sum('prix_total');

        return response()->json($stats);
    }
}
