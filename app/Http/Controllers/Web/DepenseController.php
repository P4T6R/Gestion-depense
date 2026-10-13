<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepenseAvecLignesRequest;
use App\Http\Requests\StoreDepenseRequest;
use App\Http\Requests\UpdateDepenseRequest;
use App\Models\CategorieDepense;
use App\Models\Chantier;
use App\Models\Depense;
use App\Models\Fournisseur;
use App\Models\UniteMesure;
use App\Services\DepenseService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DepenseController extends Controller
{
    public function __construct(private DepenseService $service) {}

    /**
     * Lister les dépenses selon le rôle de l'utilisateur
     */
    public function index()
    {
        $user = auth()->user();
        $query = Depense::visibleTo($user)->with(['chantier', 'utilisateur', 'categorie', 'unite']);

        // Filtrer selon le rôle
        switch ($user->role) {
            case 'administrateur':
                // Admin voit toutes les dépenses
                break;

            case 'chef_chantier':
                // Chef de chantier voit seulement ses dépenses créées
                $query->where('user_id', $user->id);
                break;

            case 'directeur':
                // Directeur voit toutes les dépenses de tous les chantiers
                // Pas de filtre - c'est le directeur de la structure
                break;

            case 'comptable':
                // Comptable voit toutes les dépenses (historique complet)
                // Pas de filtre - il peut voir en_attente, acceptee, validee, rejetee
                break;
        }

        $stats = [];
        foreach (['en_attente', 'acceptee', 'validee', 'rejetee'] as $status) {
            $stats[$status] = (clone $query)->where('statut_validation', $status)->count();
            $stats['montant_'.$status] = (clone $query)->where('statut_validation', $status)->sum('prix_total');
        }
        $depenses = $query->latest()->paginate(15);

        return view('depenses.index', compact('depenses', 'stats'));
    }

    /**
     * Afficher le formulaire de création de dépense (Chef de chantier et Directeur)
     */
    public function create()
    {
        $user = auth()->user();

        // Vérifier que c'est un chef de chantier ou un directeur
        if (! in_array($user->role, ['chef_chantier', 'directeur'])) {
            abort(403, 'Seul un chef de chantier ou un directeur peut créer une dépense.');
        }

        // Récupérer les chantiers selon le rôle
        if ($user->role === 'chef_chantier') {
            // Chef de chantier : uniquement ses chantiers assignés
            $chantiers = $user->chantiers()->get();
        } else {
            // Directeur : tous les chantiers
            $chantiers = Chantier::all();
        }

        // Filtrer les catégories selon le rôle
        if ($user->role === 'directeur') {
            // Directeur : uniquement les catégories administratives
            $categories = CategorieDepense::actives()->where('type', 'administrative')->get();
        } else {
            // Chef de chantier : uniquement les catégories de chantier
            $categories = CategorieDepense::actives()->where('type', 'chantier')->get();
        }

        $unites = UniteMesure::actives()->get();
        $fournisseurs = Fournisseur::where('is_active', true)->orderBy('nom')->get(['id', 'nom']);

        return view('depenses.create', compact('chantiers', 'categories', 'unites', 'fournisseurs'));
    }

    /**
     * Enregistrer une nouvelle dépense
     */
    public function store(Request $request)
    {
        $form = $request->has('lignes')
            ? app(StoreDepenseAvecLignesRequest::class)
            : app(StoreDepenseRequest::class);
        $data = $form->validated();
        abort_unless($request->user()->hasAccessToChantier($data['chantier_id']), 403);
        $data['user_id'] = $request->user()->id;
        $files = $request->hasFile('justificatif') ? [$request->file('justificatif')] : [];
        $this->service->create($data, $request->user(), $files);

        return redirect()->route('depenses.index')->with('success', 'Dépense créée avec succès.');
    }

    /**
     * Enregistrer une dépense avec plusieurs lignes (multi-articles)
     */

    /**
     * Afficher les détails d'une dépense
     */
    public function show(Depense $depense)
    {
        $this->authorize('view', $depense);
        $depense->load(['chantier', 'utilisateur', 'validateur', 'categorie', 'unite', 'justificatifs', 'lignes.categorieDepense', 'lignes.uniteMesure', 'historique']);
        $rapprochements = collect();
        if ($depense->fournisseur_id && $depense->date_facture) {
            $rapprochements = Depense::visibleTo(auth()->user())
                ->where('fournisseur_id', $depense->fournisseur_id)
                ->where('prix_total', $depense->prix_total)
                ->whereDate('date_facture', $depense->date_facture->toDateString())
                ->whereKeyNot($depense->id)->latest()->limit(5)->get(['id', 'numero_facture', 'prix_total', 'date_facture']);
        }

        return view('depenses.show', compact('depense', 'rapprochements'));
    }

    /**
     * Approuver une dépense (Directeur seulement)
     */
    public function approve(Depense $depense)
    {
        $this->service->approve($depense, auth()->user());

        return redirect()->route('depenses.show', $depense)->with('success', 'Dépense approuvée et envoyée au comptable.');
    }

    /**
     * Rejeter une dépense en attente depuis l'écran du directeur.
     */
    public function reject(Request $request, Depense $depense)
    {
        $this->authorize('reject', $depense);
        $data = $request->validate(['motif_rejet' => 'required|string|min:10|max:10000']);
        $this->service->reject($depense, $data['motif_rejet'], $request->user());

        return redirect()->route('depenses.show', $depense)->with('success', 'Dépense rejetée. Le motif est visible sur la fiche.');
    }

    /**
     * Valider une dépense (Comptable seulement)
     */
    public function validateDepense(Request $request, Depense $depense)
    {
        $data = $request->validate([
            'action' => 'required|in:accept,reject',
            'motif_rejet' => 'required_if:action,reject|nullable|string|max:10000',
        ]);
        if ($data['action'] === 'accept') {
            $this->service->validate($depense, $request->user());
        } else {
            $this->service->reject($depense, $data['motif_rejet'], $request->user());
        }

        return redirect()->route('depenses.show', $depense)->with('success', 'Dépense traitée avec succès.');
    }

    /**
     * Modifier une dépense en attente ou corriger une facture rejetée.
     */
    public function edit(Depense $depense)
    {
        $this->authorize('update', $depense);
        $user = auth()->user();
        $chantiers = $user->isDirecteur() ? Chantier::all() : $user->chantiers()->get();
        $categories = CategorieDepense::actives()->get();
        $unites = UniteMesure::actives()->get();
        $fournisseurs = Fournisseur::where('is_active', true)->orderBy('nom')->get(['id', 'nom']);
        $depense->load('lignes');
        $view = $depense->lignes->isNotEmpty() ? 'depenses.create' : 'depenses.edit';

        return view($view, compact('depense', 'chantiers', 'categories', 'unites', 'fournisseurs'));
    }

    /**
     * Mettre à jour une dépense
     */
    public function update(UpdateDepenseRequest $request, Depense $depense)
    {
        $wasRejected = $depense->statut_validation === 'rejetee';
        $data = $request->validated();
        abort_unless($request->user()->hasAccessToChantier($data['chantier_id'] ?? $depense->chantier_id), 403);
        $files = $request->hasFile('justificatif') ? [$request->file('justificatif')] : [];
        $this->service->update($depense, $data, $request->user(), $files);

        return redirect()->route('depenses.show', $depense)->with('success', $wasRejected
            ? 'Facture corrigée et soumise à nouveau '.($depense->circuit_validation_directe ? 'à la comptable.' : 'au directeur.')
            : 'Dépense modifiée avec succès.');
    }

    /**
     * Supprimer une dépense (Créateur seulement, avant approbation)
     */
    public function destroy(Depense $depense)
    {
        $this->service->delete($depense, auth()->user());

        return redirect()->route('depenses.index')->with('success', 'Dépense supprimée avec succès.');
    }

    /**
     * Page de rapports avec filtres
     */
    public function rapports(Request $request)
    {
        $user = auth()->user();

        // Seuls admin et comptable peuvent accéder aux rapports
        if (! in_array($user->role, ['administrateur', 'comptable'])) {
            abort(403, 'Accès non autorisé.');
        }

        $query = Depense::visibleTo($user)->with(['chantier', 'utilisateur', 'categorie', 'unite']);

        // Filtres
        $filters = [];

        // Filtre par période
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
            $filters['date_debut'] = $request->date_debut;
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
            $filters['date_fin'] = $request->date_fin;
        }

        // Filtre par chantier
        if ($request->filled('chantier_id')) {
            $query->where('chantier_id', $request->chantier_id);
            $filters['chantier_id'] = $request->chantier_id;
        }

        // Filtre par catégorie
        if ($request->filled('categorie_id')) {
            $query->inCategory($request->categorie_id);
            $filters['categorie_id'] = $request->categorie_id;
        }

        // Filtre par statut
        if ($request->filled('statut_validation')) {
            $query->where('statut_validation', $request->statut_validation);
            $filters['statut_validation'] = $request->statut_validation;
        }

        $depenses = $query->latest()->get();

        // Statistiques
        $stats = [
            'total_depenses' => $depenses->count(),
            'montant_total' => \App\Support\Amount::sum($depenses->pluck('prix_total')),
            'en_attente' => $depenses->where('statut_validation', 'en_attente')->count(),
            'acceptees' => $depenses->where('statut_validation', 'acceptee')->count(),
            'validees' => $depenses->where('statut_validation', 'validee')->count(),
            'rejetees' => $depenses->where('statut_validation', 'rejetee')->count(),
            'montant_valide' => \App\Support\Amount::sum($depenses->where('statut_validation', 'validee')->pluck('prix_total')),
        ];

        // Groupement par catégorie
        $parCategorie = app(\App\Services\ExpenseReportingService::class)->byCategory($depenses);

        // Groupement par chantier
        $parChantier = $depenses->groupBy('chantier_id')->map(function ($items) {
            return [
                'nom' => $items->first()->chantier->nom ?? 'N/A',
                'count' => $items->count(),
                'montant' => $items->sum('prix_total'),
            ];
        });

        $chantiers = Chantier::all();
        $categories = CategorieDepense::all();

        return view('depenses.rapports', compact('depenses', 'stats', 'parCategorie', 'parChantier', 'chantiers', 'categories', 'filters'));
    }

    /**
     * Export PDF
     */
    public function exportPdf(Request $request)
    {
        $user = auth()->user();

        if (! in_array($user->role, ['administrateur', 'comptable'])) {
            abort(403, 'Accès non autorisé.');
        }

        $query = Depense::visibleTo($user)->with(['chantier', 'utilisateur', 'categorie', 'unite']);
        $this->applyFilters($query, $request);
        $depenses = $query->latest()->get();

        $stats = [
            'total' => $depenses->count(),
            'montant_total' => \App\Support\Amount::sum($depenses->pluck('prix_total')),
            'montant_valide' => \App\Support\Amount::sum($depenses->where('statut_validation', 'validee')->pluck('prix_total')),
        ];

        $pdf = Pdf::loadView('depenses.pdf', compact('depenses', 'stats'));

        return $pdf->download('rapport_depenses_'.date('Y-m-d').'.pdf');
    }

    /**
     * Export Excel
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();

        if (! in_array($user->role, ['administrateur', 'comptable'])) {
            abort(403, 'Accès non autorisé.');
        }

        $query = Depense::visibleTo($user)->with(['chantier', 'utilisateur', 'categorie', 'unite']);
        $this->applyFilters($query, $request);
        $depenses = $query->latest()->get();

        $path = app(\App\Support\ExpenseWorkbook::class)->create($depenses);

        return response()->streamDownload(function () use ($path) {
            try {
                readfile($path);
            } finally {
                unlink($path);
            }
        }, 'rapport_depenses_'.date('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export CSV
     */
    public function exportCsv(Request $request)
    {
        $user = auth()->user();

        if (! in_array($user->role, ['administrateur', 'comptable'])) {
            abort(403, 'Accès non autorisé.');
        }

        $query = Depense::visibleTo($user)->with(['chantier', 'utilisateur', 'categorie', 'unite']);
        $this->applyFilters($query, $request);
        $depenses = $query->latest()->get();

        $filename = 'rapport_depenses_'.date('Y-m-d').'.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($depenses) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Chantier', 'Produit', 'Quantité', 'Unité', 'Prix Unitaire', 'Prix Total', 'Catégorie', 'Statut', 'Créé le']);

            foreach ($depenses as $depense) {
                fputcsv($file, [
                    $depense->id,
                    $this->csvText($depense->chantier->nom ?? 'N/A'),
                    $this->csvText($depense->libelle),
                    $depense->quantite,
                    $this->csvText($depense->unite->nom ?? 'N/A'),
                    $depense->prix_unitaire,
                    $depense->prix_total,
                    $this->csvText($depense->categorie->nom ?? 'N/A'),
                    ucfirst(str_replace('_', ' ', $depense->statut_validation)),
                    $depense->created_at->format('d/m/Y'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Appliquer les filtres à une requête
     */
    private function csvText(string $value): string
    {
        return preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value;
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('date_debut')) {
            $query->whereDate('created_at', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('created_at', '<=', $request->date_fin);
        }
        if ($request->filled('chantier_id')) {
            $query->where('chantier_id', $request->chantier_id);
        }
        if ($request->filled('categorie_id')) {
            $query->inCategory($request->categorie_id);
        }
        if ($request->filled('statut_validation')) {
            $query->where('statut_validation', $request->statut_validation);
        }
    }
}
