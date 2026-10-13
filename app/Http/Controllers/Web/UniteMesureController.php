<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\UniteMesure;
use Illuminate\Http\Request;

class UniteMesureController extends Controller
{
    /**
     * Lister les unités (Admin seulement)
     */
    public function index()
    {
        $unites = UniteMesure::latest()->paginate(15);

        return view('unites.index', compact('unites'));
    }

    /**
     * Afficher le formulaire de création (Admin seulement)
     */
    public function create()
    {
        return view('unites.create');
    }

    /**
     * Enregistrer une nouvelle unité (Admin seulement)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:unites_mesure,nom',
            'symbole' => 'required|string|max:20|unique:unites_mesure,symbole',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        UniteMesure::create($validated);

        return redirect()->route('unites.index')->with('success', 'Unité créée avec succès.');
    }

    /**
     * Afficher les détails d'une unité (Admin seulement)
     */
    public function show(UniteMesure $unite)
    {
        $unite->loadCount('depenses');

        return view('unites.show', compact('unite'));
    }

    /**
     * Afficher le formulaire d'édition (Admin seulement)
     */
    public function edit(UniteMesure $unite)
    {
        return view('unites.edit', compact('unite'));
    }

    /**
     * Mettre à jour une unité (Admin seulement)
     */
    public function update(Request $request, UniteMesure $unite)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255|unique:unites_mesure,nom,'.$unite->id,
            'symbole' => 'required|string|max:20|unique:unites_mesure,symbole,'.$unite->id,
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $unite->update($validated);

        return redirect()->route('unites.index')->with('success', 'Unité mise à jour avec succès.');
    }

    /**
     * Activer/Désactiver une unité (Admin seulement)
     */
    public function toggleActive(UniteMesure $unite)
    {
        $unite->update(['is_active' => ! $unite->is_active]);
        $message = $unite->is_active ? 'Unité activée.' : 'Unité désactivée.';

        return redirect()->route('unites.index')->with('success', $message);
    }

    /**
     * Supprimer une unité (Admin seulement)
     */
    public function destroy(UniteMesure $unite)
    {
        // Vérifier qu'il n'y a pas de dépenses associées
        if ($unite->isUsed()) {
            return redirect()->route('unites.index')->with('error', 'Impossible de supprimer une unité avec des dépenses.');
        }

        $unite->delete();

        return redirect()->route('unites.index')->with('success', 'Unité supprimée avec succès.');
    }
}
