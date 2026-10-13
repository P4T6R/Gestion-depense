<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\UniteMesure;
use Illuminate\Http\Request;

class UniteMesureController extends Controller
{
    /**
     * Liste des unités
     */
    public function index(Request $request)
    {
        $query = UniteMesure::query();

        // Filtrer uniquement les actives par défaut
        if ($request->has('actives_only') && $request->actives_only) {
            $query->actives();
        }

        $unites = $query->orderBy('nom')->get();

        return response()->json($unites);
    }

    /**
     * Créer une unité (Admin uniquement)
     */
    public function store(Request $request)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:unites_mesure,nom',
            'symbole' => 'required|string|max:20|unique:unites_mesure,symbole',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $unite = UniteMesure::create($validated);

        return response()->json([
            'message' => 'Unité créée avec succès',
            'unite' => $unite,
        ], 201);
    }

    /**
     * Détails d'une unité
     */
    public function show(UniteMesure $unite)
    {
        $unite->load(['depenses' => fn ($query) => $query->visibleTo(request()->user())]);
        $unite->loadCount('depenses');

        return response()->json($unite);
    }

    /**
     * Modifier une unité (Admin uniquement)
     */
    public function update(Request $request, UniteMesure $unite)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255|unique:unites_mesure,nom,'.$unite->id,
            'symbole' => 'sometimes|string|max:20|unique:unites_mesure,symbole,'.$unite->id,
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $unite->update($validated);

        return response()->json([
            'message' => 'Unité mise à jour avec succès',
            'unite' => $unite,
        ]);
    }

    /**
     * Activer/Désactiver une unité (Admin uniquement)
     */
    public function toggleActive(Request $request, UniteMesure $unite)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $unite->update([
            'is_active' => ! $unite->is_active,
        ]);

        $message = $unite->is_active ? 'Unité activée' : 'Unité désactivée';

        return response()->json([
            'message' => $message,
            'unite' => $unite,
        ]);
    }

    /**
     * Supprimer une unité (Admin uniquement)
     */
    public function destroy(Request $request, UniteMesure $unite)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérifier si l'unité est utilisée
        if ($unite->isUsed()) {
            return response()->json([
                'message' => 'Impossible de supprimer une unité utilisée dans des dépenses',
            ], 422);
        }

        $unite->delete();

        return response()->json([
            'message' => 'Unité supprimée avec succès',
        ]);
    }
}
