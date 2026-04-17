<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chantier;
use App\Models\Depense;
use App\Models\Justificatif;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DepenseController extends Controller
{
    /**
     * Liste des dépenses
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Depense::with([
            'chantier', 
            'utilisateur', 
            'validateur', 
            'justificatifs',
            'categorie',
            'unite'
        ]);

        // Filtrer selon le rôle
        if ($user->isChefChantier()) {
            // Chef voit uniquement ses dépenses
            $query->where('user_id', $user->id);
        }

        // Filtres additionnels
        if ($request->has('chantier_id')) {
            $query->where('chantier_id', $request->chantier_id);
        }

        if ($request->has('statut_validation')) {
            $query->where('statut_validation', $request->statut_validation);
        }

        if ($request->has('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        $depenses = $query->latest()->paginate(20);

        return response()->json($depenses);
    }

    /**
     * Créer une dépense (Chef de chantier)
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'chantier_id' => 'required|exists:chantiers,id',
            'id_produit' => 'nullable|string|max:50',
            'nom_produit' => 'required|string|max:255',
            'unite_id' => 'required|exists:unites_mesure,id',
            'quantite' => 'required|numeric|min:0.01',
            'prix_unitaire' => 'required|numeric|min:0',
            'categorie_id' => 'required|exists:categories_depenses,id',
            'observations' => 'nullable|string',
            'statut_paiement' => 'required|in:paye,non_paye',
            'statut_livraison' => 'required|in:livre,non_livre',
            // 'justificatifs' => 'required|array|min:1',
            // 'justificatifs.*' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB max
        ]);

        // Vérifier l'accès au chantier
        $chantier = Chantier::findOrFail($validated['chantier_id']);
        if (!$user->hasAccessToChantier($chantier->id)) {
            return response()->json(['message' => 'Accès refusé à ce chantier'], 403);
        }

        // Calculer le prix total
        $prixTotal = $validated['quantite'] * $validated['prix_unitaire'];

        // Créer la dépense
        $depense = Depense::create([
            'chantier_id' => $validated['chantier_id'],
            'user_id' => $user->id,
            'id_produit' => $validated['id_produit'] ?? null,
            'nom_produit' => $validated['nom_produit'],
            'unite_id' => $validated['unite_id'],
            'quantite' => $validated['quantite'],
            'prix_unitaire' => $validated['prix_unitaire'],
            'prix_total' => $prixTotal,
            'categorie_id' => $validated['categorie_id'],
            'observations' => $validated['observations'] ?? null,
            'statut_paiement' => $validated['statut_paiement'],
            'statut_livraison' => $validated['statut_livraison'],
        ]);

        // Enregistrer les justificatifs si présents
        if ($request->hasFile('justificatifs')) {
            foreach ($request->file('justificatifs') as $file) {
                $path = $file->store('justificatifs', 'public');
                
                Justificatif::create([
                    'depense_id' => $depense->id,
                    'nom_fichier' => $file->getClientOriginalName(),
                    'chemin_fichier' => $path,
                    'type_fichier' => $file->getMimeType(),
                    'taille_fichier' => $file->getSize(),
                ]);
            }
        }

        return response()->json([
            'message' => 'Dépense créée avec succès',
            'depense' => $depense->load(['justificatifs', 'categorie', 'unite']),
        ], 201);
    }

    /**
     * Créer une dépense avec plusieurs lignes (nouvelle méthode)
     * Une facture peut contenir plusieurs articles
     */
    public function storeAvecLignes(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
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
        ]);

        // Vérifier l'accès au chantier
        $chantier = Chantier::findOrFail($validated['chantier_id']);
        if (!$user->hasAccessToChantier($chantier->id)) {
            return response()->json(['message' => 'Accès refusé à ce chantier'], 403);
        }

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
                'statut_paiement' => $validated['statut_paiement'] ?? 'non_paye',
                'statut_livraison' => $validated['statut_livraison'] ?? 'non_livre',
                'prix_total' => 0, // Sera recalculé après insertion des lignes
            ]);

            // Créer les lignes de dépense
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

            // Mettre à jour le montant total de la dépense
            $depense->update(['prix_total' => $totalMontant]);

            // Enregistrer les justificatifs si présents
            if ($request->hasFile('justificatifs')) {
                foreach ($request->file('justificatifs') as $file) {
                    $path = $file->store('justificatifs', 'public');
                    
                    Justificatif::create([
                        'depense_id' => $depense->id,
                        'nom_fichier' => $file->getClientOriginalName(),
                        'chemin_fichier' => $path,
                        'type_fichier' => $file->getMimeType(),
                        'taille_fichier' => $file->getSize(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Dépense créée avec succès',
                'depense' => $depense->load(['lignes.categorieDepense', 'lignes.uniteMesure', 'justificatifs', 'chantier']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la création de la dépense',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Détails d'une dépense
     */
    public function show(Request $request, Depense $depense)
    {
        $user = $request->user();

        // Vérifier l'accès
        if ($user->isChefChantier() && $depense->user_id !== $user->id) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        if ($user->isChefChantier() && !$user->hasAccessToChantier($depense->chantier_id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $depense->load([
            'chantier', 
            'utilisateur', 
            'validateur', 
            'justificatifs',
            'categorie',
            'unite'
        ]);

        return response()->json($depense);
    }

    /**
     * Modifier une dépense (Avant validation uniquement)
     */
    public function update(Request $request, Depense $depense)
    {
        $user = $request->user();

        // Vérifier les permissions
        if ($depense->user_id !== $user->id && !$user->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Ne peut pas modifier une dépense déjà validée ou rejetée
        if ($depense->statut_validation !== 'en_attente') {
            return response()->json([
                'message' => 'Impossible de modifier une dépense déjà traitée'
            ], 422);
        }

        $validated = $request->validate([
            'id_produit' => 'sometimes|nullable|string|max:50',
            'nom_produit' => 'sometimes|string|max:255',
            'unite_id' => 'sometimes|exists:unites_mesure,id',
            'quantite' => 'sometimes|numeric|min:0.01',
            'prix_unitaire' => 'sometimes|numeric|min:0',
            'categorie_id' => 'sometimes|exists:categories_depenses,id',
            'observations' => 'sometimes|nullable|string',
            'statut_paiement' => 'sometimes|in:paye,non_paye',
            'statut_livraison' => 'sometimes|in:livre,non_livre',
        ]);

        $depense->update($validated);

        return response()->json([
            'message' => 'Dépense mise à jour avec succès',
            'depense' => $depense->load(['categorie', 'unite']),
        ]);
    }

    /**
     * Valider une dépense (Comptable)
     */
    public function valider(Request $request, Depense $depense)
    {
        $user = $request->user();

        if (!$user->isComptable() && !$user->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        if ($depense->statut_validation !== 'en_attente') {
            return response()->json([
                'message' => 'Cette dépense a déjà été traitée'
            ], 422);
        }

        try {
            $depense->valider($user);

            return response()->json([
                'message' => 'Dépense validée avec succès',
                'depense' => $depense->fresh()->load(['validateur', 'categorie', 'unite']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Rejeter une dépense (Comptable)
     */
    public function rejeter(Request $request, Depense $depense)
    {
        $user = $request->user();

        if (!$user->isComptable() && !$user->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'motif_rejet' => 'required|string|min:10',
        ]);

        if ($depense->statut_validation !== 'en_attente') {
            return response()->json([
                'message' => 'Cette dépense a déjà été traitée'
            ], 422);
        }

        try {
            $depense->rejeter($user, $validated['motif_rejet']);

            return response()->json([
                'message' => 'Dépense rejetée',
                'depense' => $depense->fresh()->load(['validateur', 'categorie', 'unite']),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Supprimer une dépense (Avant validation uniquement)
     */
    public function destroy(Request $request, Depense $depense)
    {
        $user = $request->user();

        // Seul le créateur ou un admin peut supprimer
        if ($depense->user_id !== $user->id && !$user->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Ne peut pas supprimer une dépense validée
        if ($depense->statut_validation === 'validee') {
            return response()->json([
                'message' => 'Impossible de supprimer une dépense validée'
            ], 422);
        }

        $depense->delete();

        return response()->json([
            'message' => 'Dépense supprimée avec succès',
        ]);
    }

    /**
     * Statistiques des dépenses
     */
    public function statistiques(Request $request)
    {
        $user = $request->user();

        $query = Depense::query();

        // Filtrer selon le rôle
        if ($user->isChefChantier()) {
            $query->where('user_id', $user->id);
        }

        if ($request->has('chantier_id')) {
            $query->where('chantier_id', $request->chantier_id);
        }

        $stats = [
            'total_depenses' => $query->count(),
            'en_attente' => (clone $query)->where('statut_validation', 'en_attente')->count(),
            'validees' => (clone $query)->where('statut_validation', 'validee')->count(),
            'rejetees' => (clone $query)->where('statut_validation', 'rejetee')->count(),
            'montant_total_valide' => (clone $query)->where('statut_validation', 'validee')->sum('prix_total'),
            'montant_en_attente' => (clone $query)->where('statut_validation', 'en_attente')->sum('prix_total'),
        ];

        return response()->json($stats);
    }
}