<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CategorieDepense;
use Illuminate\Http\Request;

class CategorieDepenseController extends Controller
{
    /**
     * Liste des catégories
     */
    public function index(Request $request)
    {
        $query = CategorieDepense::query();

        // Filtrer uniquement les actives par défaut
        if ($request->has('actives_only') && $request->actives_only) {
            $query->actives();
        }

        $categories = $query->orderBy('nom')->get();

        return response()->json($categories);
    }

    /**
     * Créer une catégorie (Admin uniquement)
     */
    public function store(Request $request)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:categories_depenses,nom',
            'code' => 'required|string|max:50|unique:categories_depenses,code',
            'type' => 'sometimes|in:chantier,administrative',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $categorie = CategorieDepense::create($validated);

        return response()->json([
            'message' => 'Catégorie créée avec succès',
            'categorie' => $categorie,
        ], 201);
    }

    /**
     * Détails d'une catégorie
     */
    public function show(CategorieDepense $categorie)
    {
        $categorie->load(['depenses' => fn ($query) => $query->visibleTo(request()->user())]);
        $categorie->loadCount('depenses');

        return response()->json($categorie);
    }

    /**
     * Modifier une catégorie (Admin uniquement)
     */
    public function update(Request $request, CategorieDepense $categorie)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'nom' => 'sometimes|string|max:255|unique:categories_depenses,nom,'.$categorie->id,
            'code' => 'sometimes|string|max:50|unique:categories_depenses,code,'.$categorie->id,
            'type' => 'sometimes|in:chantier,administrative',
            'description' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $categorie->update($validated);

        return response()->json([
            'message' => 'Catégorie mise à jour avec succès',
            'categorie' => $categorie,
        ]);
    }

    /**
     * Activer/Désactiver une catégorie (Admin uniquement)
     */
    public function toggleActive(Request $request, CategorieDepense $categorie)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $categorie->update([
            'is_active' => ! $categorie->is_active,
        ]);

        $message = $categorie->is_active ? 'Catégorie activée' : 'Catégorie désactivée';

        return response()->json([
            'message' => $message,
            'categorie' => $categorie,
        ]);
    }

    /**
     * Supprimer une catégorie (Admin uniquement)
     */
    public function destroy(Request $request, CategorieDepense $categorie)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Vérifier si la catégorie est utilisée
        if ($categorie->isUsed()) {
            return response()->json([
                'message' => 'Impossible de supprimer une catégorie utilisée dans des dépenses',
            ], 422);
        }

        $categorie->delete();

        return response()->json([
            'message' => 'Catégorie supprimée avec succès',
        ]);
    }
}
