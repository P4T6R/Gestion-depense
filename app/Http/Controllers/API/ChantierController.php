<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chantier;
use App\Models\User;
use Illuminate\Http\Request;

class ChantierController extends Controller
{
    /**
     * Liste des chantiers
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $query = Chantier::with(['createur', 'chefsChantier'])
                         ->withCount(['depenses', 'depensesValidees', 'depensesEnAttente']);

        // Si chef de chantier, ne voir que ses chantiers
        if ($user->isChefChantier()) {
            $query->whereHas('chefsChantier', function($q) use ($user) {
                $q->where('users.id', $user->id);
            });
        }

        // Filtres
        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        $chantiers = $query->latest()->get();

        return response()->json($chantiers);
    }

    /**
     * Créer un chantier (Admin uniquement)
     */
    public function store(Request $request)
    {
        if (!$request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'lieu' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'budget_alloue' => 'required|numeric|min:0',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'chef_chantier_ids' => 'nullable|array',
            'chef_chantier_ids.*' => 'exists:users,id',
        ]);

        $chantier = Chantier::create([
            'nom' => $validated['nom'],
            'lieu' => $validated['lieu'] ?? null,
            'description' => $validated['description'] ?? null,
            'budget_alloue' => $validated['budget_alloue'],
            'date_debut' => $validated['date_debut'] ?? null,
            'date_fin' => $validated['date_fin'] ?? null,
            'created_by' => $request->user()->id,
        ]);

        // Affecter les chefs de chantier
        if (isset($validated['chef_chantier_ids'])) {
            foreach ($validated['chef_chantier_ids'] as $chefId) {
                $chef = User::find($chefId);
                if ($chef && $chef->isChefChantier()) {
                    $chantier->affecterChef($chef);
                }
            }
        }

        return response()->json([
            'message' => 'Chantier créé avec succès',
            'chantier' => $chantier->load('chefsChantier'),
        ], 201);
    }

    /**
     * Détails d'un chantier
     */
    public function show(Request $request, Chantier $chantier)
    {
        $user = $request->user();

        // Vérifier l'accès
        if (!$user->hasAccessToChantier($chantier->id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $chantier->load([
            'createur',
            'chefsChantier',
            'depenses.utilisateur',
            'depenses.justificatifs',
            'depenses.categorie',
            'depenses.unite'
        ]);

        return response()->json($chantier);
    }

    /**
     * Modifier un chantier (Admin uniquement)
     */
    public function update(Request $request, Chantier $chantier)
    {
        if (!$request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255',
            'lieu' => 'sometimes|nullable|string|max:255',
            'description' => 'sometimes|nullable|string',
            'budget_alloue' => 'sometimes|numeric|min:0',
            'date_debut' => 'sometimes|nullable|date',
            'date_fin' => 'sometimes|nullable|date',
            'statut' => 'sometimes|in:actif,en_pause,termine,archive',
        ]);

        $chantier->update($validated);

        return response()->json([
            'message' => 'Chantier mis à jour avec succès',
            'chantier' => $chantier,
        ]);
    }

    /**
     * Affecter un chef au chantier (Admin uniquement)
     */
    public function affecterChef(Request $request, Chantier $chantier)
    {
        if (!$request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $chef = User::findOrFail($validated['user_id']);

        if (!$chef->isChefChantier()) {
            return response()->json([
                'message' => 'Cet utilisateur n\'est pas un chef de chantier'
            ], 422);
        }

        try {
            $chantier->affecterChef($chef);

            return response()->json([
                'message' => 'Chef de chantier affecté avec succès',
                'chantier' => $chantier->load('chefsChantier'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Retirer un chef du chantier (Admin uniquement)
     */
    public function retirerChef(Request $request, Chantier $chantier, User $chef)
    {
        if (!$request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $chantier->retirerChef($chef);

        return response()->json([
            'message' => 'Chef de chantier retiré avec succès',
        ]);
    }

    /**
     * Supprimer un chantier (Admin uniquement - Soft delete)
     */
    public function destroy(Request $request, Chantier $chantier)
    {
        if (!$request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $chantier->delete();

        return response()->json([
            'message' => 'Chantier archivé avec succès',
        ]);
    }

    /**
     * Statistiques du chantier
     */
    public function statistiques(Request $request, Chantier $chantier)
    {
        $user = $request->user();

        if (!$user->hasAccessToChantier($chantier->id)) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $stats = [
            'budget_alloue' => $chantier->budget_alloue,
            'budget_consomme' => $chantier->budget_consomme,
            'budget_restant' => $chantier->budget_restant,
            'pourcentage_consomme' => $chantier->pourcentage_consomme,
            'statut_budget' => $chantier->statut_budget,
            'total_depenses' => $chantier->depenses()->count(),
            'depenses_validees' => $chantier->depensesValidees()->count(),
            'depenses_en_attente' => $chantier->depensesEnAttente()->count(),
            'depenses_rejetees' => $chantier->depenses()->where('statut_validation', 'rejetee')->count(),
        ];

        return response()->json($stats);
    }
}