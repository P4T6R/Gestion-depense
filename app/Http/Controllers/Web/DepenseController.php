<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use App\Models\Chantier;
use App\Models\CategorieDepense;
use App\Models\UniteMesure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class DepenseController extends Controller
{
    /**
     * Lister les dépenses selon le rôle de l'utilisateur
     */
    public function index()
    {
        $user = auth()->user();
        $query = Depense::with(['chantier', 'utilisateur', 'categorie', 'unite']);

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

        $depenses = $query->latest()->paginate(15);
        return view('depenses.index', compact('depenses'));
    }

    /**
     * Afficher le formulaire de création de dépense (Chef de chantier et Directeur)
     */
    public function create()
    {
        $user = auth()->user();
        
        // Vérifier que c'est un chef de chantier ou un directeur
        if (!in_array($user->role, ['chef_chantier', 'directeur'])) {
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
        
        $unites = UniteMesure::all();

        return view('depenses.create', compact('chantiers', 'categories', 'unites'));
    }

    /**
     * Enregistrer une nouvelle dépense
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        if (!in_array($user->role, ['chef_chantier', 'directeur'])) {
            abort(403, 'Seul un chef de chantier ou un directeur peut créer une dépense.');
        }

        // Détecter si c'est une facture multi-lignes
        if ($request->has('lignes') && is_array($request->lignes)) {
            return $this->storeMultiLignes($request);
        }

        // Ancien format (1 produit)
        $validated = $request->validate([
            'chantier_id' => 'required|exists:chantiers,id',
            'nom_produit' => 'required|string|max:255',
            'quantite' => 'required|numeric|min:0.01',
            'prix_unitaire' => 'required|numeric|min:0.01',
            'unite_id' => 'required|exists:unites_mesure,id',
            'categorie_id' => 'required|exists:categories_depenses,id',
            'observations' => 'nullable|string',
            'id_produit' => 'nullable|string|max:100',
            'justificatif' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120', // Max 5MB
        ]);

        // Calculer le prix total
        $validated['prix_total'] = $validated['quantite'] * $validated['prix_unitaire'];
        $validated['user_id'] = $user->id;
        $validated['statut_validation'] = 'en_attente';
        $validated['statut_paiement'] = 'non_paye';
        $validated['statut_livraison'] = 'non_livre';

        // Retirer le justificatif des données validées avant création
        $justificatif = $request->file('justificatif');
        unset($validated['justificatif']);

        $depense = Depense::create($validated);

        // Gérer l'upload du justificatif si présent
        if ($justificatif) {
            $nomFichier = time() . '_' . $justificatif->getClientOriginalName();
            $chemin = $justificatif->storeAs('justificatifs', $nomFichier, 'public');
            
            $depense->justificatifs()->create([
                'nom_fichier' => $nomFichier,
                'chemin_fichier' => $chemin,
                'type_fichier' => $justificatif->getMimeType(),
                'taille_fichier' => $justificatif->getSize(),
            ]);
        }

        return redirect()->route('depenses.index')->with('success', 'Dépense créée avec succès.');
    }

    /**
     * Enregistrer une dépense avec plusieurs lignes (multi-articles)
     */
    protected function storeMultiLignes(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'chantier_id' => 'required|exists:chantiers,id',
            'numero_facture' => 'nullable|string|max:255',
            'fournisseur' => 'nullable|string|max:255',
            'date_facture' => 'nullable|date',
            'observations' => 'nullable|string',
            'justificatif' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            
            'lignes' => 'required|array|min:1',
            'lignes.*.designation' => 'required|string|max:255',
            'lignes.*.categorie_depense_id' => 'nullable|exists:categories_depenses,id',
            'lignes.*.unite_mesure_id' => 'nullable|exists:unites_mesure,id',
            'lignes.*.quantite' => 'required|numeric|min:0.01',
            'lignes.*.prix_unitaire' => 'required|numeric|min:0',
            'lignes.*.observations' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Créer la dépense principale
            $depense = Depense::create([
                'chantier_id' => $validated['chantier_id'],
                'user_id' => $user->id,
                'numero_facture' => $validated['numero_facture'] ?? null,
                'fournisseur' => $validated['fournisseur'] ?? null,
                'date_facture' => $validated['date_facture'] ?? now(),
                'observations' => $validated['observations'] ?? null,
                'statut_paiement' => 'non_paye',
                'statut_livraison' => 'non_livre',
                'statut_validation' => 'en_attente',
                'prix_total' => 0, // Sera recalculé
            ]);

            // Créer les lignes
            $totalMontant = 0;
            foreach ($validated['lignes'] as $ligneData) {
                $montant = $ligneData['quantite'] * $ligneData['prix_unitaire'];
                $totalMontant += $montant;

                $depense->lignes()->create([
                    'designation' => $ligneData['designation'],
                    'categorie_depense_id' => $ligneData['categorie_depense_id'] ?? null,
                    'unite_mesure_id' => $ligneData['unite_mesure_id'] ?? null,
                    'quantite' => $ligneData['quantite'],
                    'prix_unitaire' => $ligneData['prix_unitaire'],
                    'montant' => $montant,
                    'observations' => $ligneData['observations'] ?? null,
                ]);
            }

            // Mettre à jour le montant total
            $depense->update(['prix_total' => $totalMontant]);

            // Gérer le justificatif
            if ($request->hasFile('justificatif')) {
                $justificatif = $request->file('justificatif');
                $nomFichier = time() . '_' . $justificatif->getClientOriginalName();
                $chemin = $justificatif->storeAs('justificatifs', $nomFichier, 'public');
                
                $depense->justificatifs()->create([
                    'nom_fichier' => $nomFichier,
                    'chemin_fichier' => $chemin,
                    'type_fichier' => $justificatif->getMimeType(),
                    'taille_fichier' => $justificatif->getSize(),
                ]);
            }

            DB::commit();

            return redirect()->route('depenses.index')->with('success', 
                'Facture créée avec succès (' . count($validated['lignes']) . ' articles, Total: ' . number_format($totalMontant, 0, ',', ' ') . ' F)');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Erreur lors de la création: ' . $e->getMessage()]);
        }
    }

    /**
     * Afficher les détails d'une dépense
     */
    public function show(Depense $depense)
    {
        $user = auth()->user();

        // Vérifier l'accès selon le rôle
        switch ($user->role) {
            case 'chef_chantier':
                // Chef ne peut voir que ses propres dépenses
                if ($depense->user_id !== $user->id) {
                    abort(403, 'Vous n\'avez pas accès à cette dépense.');
                }
                break;
            
            case 'directeur':
                // Directeur peut voir toutes les dépenses (directeur de la structure)
                break;
            
            case 'comptable':
                // Comptable peut voir toutes les dépenses (pour validation)
                break;
            
            case 'administrateur':
                // Admin peut tout voir
                break;
        }

        $depense->load(['chantier', 'utilisateur', 'categorie', 'unite', 'justificatifs']);
        return view('depenses.show', compact('depense'));
    }

    /**
     * Approuver une dépense (Directeur seulement)
     */
    public function approve(Depense $depense)
    {
        $user = auth()->user();

        if ($user->role !== 'directeur') {
            abort(403, 'Seul un directeur peut approuver une dépense.');
        }

        // Le directeur peut approuver toutes les dépenses (directeur de la structure)
        if ($depense->statut_validation !== 'en_attente') {
            abort(403, 'Seules les dépenses en attente peuvent être approuvées.');
        }

        $depense->update(['statut_validation' => 'acceptee']);

        return redirect()->route('depenses.show', $depense)->with('success', 'Dépense approuvée et envoyée au comptable.');
    }

    /**
     * Valider une dépense (Comptable seulement)
     */
    public function validateDepense(Request $request, Depense $depense)
    {
        $user = auth()->user();

        if ($user->role !== 'comptable') {
            abort(403, 'Seul un comptable peut valider une dépense.');
        }

        // Sécurité : Le comptable ne peut valider que ce qui a été approuvé par le directeur
        if ($depense->statut_validation !== 'acceptee') {
            abort(403, 'Cette dépense doit d\'abord être approuvée par le directeur avant d\'être traitée par la comptabilité.');
        }

        if ($depense->statut_validation === 'rejetee') {
            abort(403, 'Cette dépense a déjà été rejetée.');
        }

        $action = $request->input('action');

        if ($action === 'accept') {
            $depense->update([
                'statut_validation' => 'validee',
                'valide_par' => $user->id,
                'valide_le' => now(),
            ]);

            // Mettre à jour le budget consommé du chantier
            $depense->chantier->updateBudgetConsomme();

            return redirect()->route('depenses.show', $depense)->with('success', 'Dépense validée avec succès.');
        }

        if ($action === 'reject') {
            $motif = $request->validate(['motif_rejet' => 'required|string'])['motif_rejet'];
            $depense->update([
                'statut_validation' => 'rejetee',
                'motif_rejet' => $motif,
            ]);

            return redirect()->route('depenses.show', $depense)->with('success', 'Dépense rejetée.');
        }

        abort(400, 'Action invalide.');
    }

    /**
     * Modifier une dépense (Créateur seulement, avant approbation)
     */
    public function edit(Depense $depense)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['chef_chantier', 'directeur']) || $depense->user_id !== $user->id) {
            abort(403, 'Vous n\'avez pas la permission de modifier cette dépense.');
        }

        if ($depense->statut_validation !== 'en_attente') {
            abort(403, 'Vous ne pouvez modifier que les dépenses en attente.');
        }

        $chantiers = $user->chantiers()->get();
        $categories = CategorieDepense::actives()->get();
        $unites = UniteMesure::all();

        return view('depenses.edit', compact('depense', 'chantiers', 'categories', 'unites'));
    }

    /**
     * Mettre à jour une dépense
     */
    public function update(Request $request, Depense $depense)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['chef_chantier', 'directeur']) || $depense->user_id !== $user->id) {
            abort(403, 'Vous n\'avez pas la permission de modifier cette dépense.');
        }

        if ($depense->statut_validation !== 'en_attente') {
            abort(403, 'Vous ne pouvez modifier que les dépenses en attente.');
        }

        $validated = $request->validate([
            'chantier_id' => 'required|exists:chantiers,id',
            'nom_produit' => 'required|string|max:255',
            'quantite' => 'required|numeric|min:0.01',
            'prix_unitaire' => 'required|numeric|min:0.01',
            'unite_id' => 'required|exists:unites_mesure,id',
            'categorie_id' => 'required|exists:categories_depenses,id',
            'observations' => 'nullable|string',
            'id_produit' => 'nullable|string|max:100',
            'justificatif' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
        ]);

        $validated['prix_total'] = $validated['quantite'] * $validated['prix_unitaire'];
        
        // Gérer l'upload du justificatif si présent
        $justificatif = $request->file('justificatif');
        unset($validated['justificatif']);
        
        $depense->update($validated);

        if ($justificatif) {
            // Supprimer l'ancien justificatif s'il existe
            if ($depense->justificatifs->count() > 0) {
                $oldJustif = $depense->justificatifs->first();
                \Storage::disk('public')->delete($oldJustif->chemin_fichier);
                $oldJustif->delete();
            }
            
            // Uploader le nouveau justificatif
            $nomFichier = time() . '_' . $justificatif->getClientOriginalName();
            $chemin = $justificatif->storeAs('justificatifs', $nomFichier, 'public');
            
            $depense->justificatifs()->create([
                'nom_fichier' => $nomFichier,
                'chemin_fichier' => $chemin,
                'type_fichier' => $justificatif->getMimeType(),
                'taille_fichier' => $justificatif->getSize(),
            ]);
        }

        return redirect()->route('depenses.show', $depense)->with('success', 'Dépense modifiée avec succès.');
    }

    /**
     * Supprimer une dépense (Créateur seulement, avant approbation)
     */
    public function destroy(Depense $depense)
    {
        $user = auth()->user();

        if (!in_array($user->role, ['chef_chantier', 'directeur']) || $depense->user_id !== $user->id) {
            abort(403, 'Vous n\'avez pas la permission de supprimer cette dépense.');
        }

        if ($depense->statut_validation !== 'en_attente') {
            abort(403, 'Vous ne pouvez supprimer que les dépenses en attente.');
        }

        $depense->delete();

        return redirect()->route('depenses.index')->with('success', 'Dépense supprimée avec succès.');
    }

    /**
     * Page de rapports avec filtres
     */
    public function rapports(Request $request)
    {
        $user = auth()->user();
        
        // Seuls admin et comptable peuvent accéder aux rapports
        if (!in_array($user->role, ['administrateur', 'comptable'])) {
            abort(403, 'Accès non autorisé.');
        }

        $query = Depense::with(['chantier', 'utilisateur', 'categorie', 'unite']);

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
            $query->where('categorie_id', $request->categorie_id);
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
            'montant_total' => $depenses->sum('prix_total'),
            'en_attente' => $depenses->where('statut_validation', 'en_attente')->count(),
            'acceptees' => $depenses->where('statut_validation', 'acceptee')->count(),
            'validees' => $depenses->where('statut_validation', 'validee')->count(),
            'rejetees' => $depenses->where('statut_validation', 'rejetee')->count(),
            'montant_valide' => $depenses->where('statut_validation', 'validee')->sum('prix_total'),
        ];

        // Groupement par catégorie
        $parCategorie = $depenses->groupBy('categorie_id')->map(function($items) {
            return [
                'nom' => $items->first()->categorie->nom ?? 'N/A',
                'count' => $items->count(),
                'montant' => $items->sum('prix_total'),
            ];
        });

        // Groupement par chantier
        $parChantier = $depenses->groupBy('chantier_id')->map(function($items) {
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
        
        if (!in_array($user->role, ['administrateur', 'comptable'])) {
            abort(403, 'Accès non autorisé.');
        }

        $query = Depense::with(['chantier', 'utilisateur', 'categorie', 'unite']);
        $this->applyFilters($query, $request);
        $depenses = $query->latest()->get();

        $stats = [
            'total' => $depenses->count(),
            'montant_total' => $depenses->sum('prix_total'),
            'montant_valide' => $depenses->where('statut_validation', 'validee')->sum('prix_total'),
        ];

        $pdf = Pdf::loadView('depenses.pdf', compact('depenses', 'stats'));
        return $pdf->download('rapport_depenses_' . date('Y-m-d') . '.pdf');
    }

    /**
     * Export Excel
     */
    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        
        if (!in_array($user->role, ['administrateur', 'comptable'])) {
            abort(403, 'Accès non autorisé.');
        }

        $query = Depense::with(['chantier', 'utilisateur', 'categorie', 'unite']);
        $this->applyFilters($query, $request);
        $depenses = $query->latest()->get();

        Excel::create('Rapport_Depenses_' . date('Y-m-d'), function($excel) use ($depenses) {
            $excel->sheet('Dépenses', function($sheet) use ($depenses) {
                $sheet->setOrientation('landscape');
                
                $data = [['ID', 'Chantier', 'Produit', 'Quantité', 'Unité', 'Prix Unit.', 'Prix Total', 'Catégorie', 'Statut', 'Créé le']];
                
                foreach ($depenses as $depense) {
                    $data[] = [
                        $depense->id,
                        $depense->chantier->nom ?? 'N/A',
                        $depense->nom_produit,
                        $depense->quantite,
                        $depense->unite->nom ?? 'N/A',
                        $depense->prix_unitaire,
                        $depense->prix_total,
                        $depense->categorie->nom ?? 'N/A',
                        ucfirst(str_replace('_', ' ', $depense->statut_validation)),
                        $depense->created_at->format('d/m/Y'),
                    ];
                }
                
                $sheet->fromArray($data, null, 'A1', false, false);
                $sheet->row(1, function($row) {
                    $row->setFontWeight('bold');
                    $row->setBackground('#3B82F6');
                    $row->setFontColor('#FFFFFF');
                });
            });
        })->export('xlsx');
    }

    /**
     * Export CSV
     */
    public function exportCsv(Request $request)
    {
        $user = auth()->user();
        
        if (!in_array($user->role, ['administrateur', 'comptable'])) {
            abort(403, 'Accès non autorisé.');
        }

        $query = Depense::with(['chantier', 'utilisateur', 'categorie', 'unite']);
        $this->applyFilters($query, $request);
        $depenses = $query->latest()->get();

        $filename = 'rapport_depenses_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($depenses) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Chantier', 'Produit', 'Quantité', 'Unité', 'Prix Unitaire', 'Prix Total', 'Catégorie', 'Statut', 'Créé le']);

            foreach ($depenses as $depense) {
                fputcsv($file, [
                    $depense->id,
                    $depense->chantier->nom ?? 'N/A',
                    $depense->nom_produit,
                    $depense->quantite,
                    $depense->unite->nom ?? 'N/A',
                    $depense->prix_unitaire,
                    $depense->prix_total,
                    $depense->categorie->nom ?? 'N/A',
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
            $query->where('categorie_id', $request->categorie_id);
        }
        if ($request->filled('statut_validation')) {
            $query->where('statut_validation', $request->statut_validation);
        }
    }
}
