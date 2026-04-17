<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CategorieDepense;
use Illuminate\Http\Request;

class CategorieDepenseController extends Controller
{
    /**
     * Lister les catégories (Admin seulement)
     */
    public function index()
    {
        $categories = CategorieDepense::latest()->paginate(15);
        return view('categories.index', compact('categories'));
    }

    /**
     * Afficher le formulaire de création (Admin seulement)
     */
    public function create()
    {
        return view('categories.create');
    }

    /**
     * Enregistrer une nouvelle catégorie (Admin seulement)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:categories_depenses,nom',
            'code' => 'required|string|max:10|unique:categories_depenses,code',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        CategorieDepense::create($validated);

        return redirect()->route('categories.index')->with('success', 'Catégorie créée avec succès.');
    }

    /**
     * Afficher les détails d'une catégorie (Admin seulement)
     */
    public function show(CategorieDepense $categorie)
    {
        $categorie->loadCount('depenses');
        return view('categories.show', compact('categorie'));
    }

    /**
     * Afficher le formulaire d'édition (Admin seulement)
     */
    public function edit(CategorieDepense $categorie)
    {
        return view('categories.edit', compact('categorie'));
    }

    /**
     * Mettre à jour une catégorie (Admin seulement)
     */
    public function update(Request $request, CategorieDepense $categorie)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:categories_depenses,nom,' . $categorie->id,
            'code' => 'required|string|max:10|unique:categories_depenses,code,' . $categorie->id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $validated['is_active'] ?? $categorie->is_active;

        $categorie->update($validated);

        return redirect()->route('categories.index')->with('success', 'Catégorie mise à jour avec succès.');
    }

    /**
     * Activer/Désactiver une catégorie (Admin seulement)
     */
    public function toggleActive(CategorieDepense $categorie)
    {
        $categorie->update(['is_active' => !$categorie->is_active]);
        $message = $categorie->is_active ? 'Catégorie activée.' : 'Catégorie désactivée.';
        return redirect()->route('categories.index')->with('success', $message);
    }

    /**
     * Supprimer une catégorie (Admin seulement)
     */
    public function destroy(CategorieDepense $categorie)
    {
        // Vérifier qu'il n'y a pas de dépenses associées
        if ($categorie->depenses()->count() > 0) {
            return redirect()->route('categories.index')->with('error', 'Impossible de supprimer une catégorie avec des dépenses.');
        }

        $categorie->delete();

        return redirect()->route('categories.index')->with('success', 'Catégorie supprimée avec succès.');
    }
}
