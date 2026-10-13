<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Chantier;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ChantierController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Filtrer selon le rôle
        if ($user->role === 'chef_chantier') {
            // Seul le chef de chantier voit uniquement ses chantiers assignés
            $chantiers = $user->chantiers()->with('chefsChantier')->get();
        } else {
            // Admin, Directeur, Comptable voient tous les chantiers
            $chantiers = Chantier::with('chefsChantier')->get();
        }

        return view('chantiers.index', compact('chantiers'));
    }

    public function show(Chantier $chantier)
    {
        $user = auth()->user();

        // Vérifier l'accès selon le rôle
        if ($user->role === 'chef_chantier') {
            // Seul le chef de chantier ne peut voir que ses chantiers assignés
            $chantiersIds = $user->chantiers->pluck('id')->toArray();
            if (! in_array($chantier->id, $chantiersIds)) {
                abort(403, 'Vous n\'avez pas accès à ce chantier.');
            }
        }

        $chantier->load(['chefsChantier', 'depenses' => fn ($q) => $q->visibleTo($user)->with(['utilisateur', 'categorie', 'unite'])]);
        $chantier->setAttribute('depenses_count', $chantier->depenses->count());

        return view('chantiers.show', compact('chantier'));
    }

    public function create()
    {
        $chefs = User::where('role', 'chef_chantier')->get();

        return view('chantiers.create', compact('chefs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'lieu' => 'required|string|max:255',
            'budget_alloue' => 'required|numeric|decimal:0,2|min:0|max:9999999999999.99',
            'date_debut' => 'required|date',
            'description' => 'nullable|string',
            'chef_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'chef_chantier')->where('is_active', true)->whereNull('deleted_at')],
        ]);

        $chantier = DB::transaction(function () use ($validated, $request) {
            $chantier = Chantier::create([
                'nom' => $validated['nom'],
                'lieu' => $validated['lieu'],
                'budget_alloue' => $validated['budget_alloue'],
                'budget_consomme' => 0,
                'date_debut' => $validated['date_debut'],
                'description' => $validated['description'] ?? null,
                'statut' => 'actif',
                'created_by' => $request->user()->id,
            ]);

            $chantier->affecterChef(User::findOrFail($validated['chef_id']));

            return $chantier;
        });

        return redirect()->route('chantiers.index')->with('success', 'Chantier créé avec succès.');
    }

    public function edit(Chantier $chantier)
    {
        $chefs = User::where('role', 'chef_chantier')->get();
        $chantier->load('chefsChantier');

        return view('chantiers.edit', compact('chantier', 'chefs'));
    }

    public function update(Request $request, Chantier $chantier)
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:255',
            'lieu' => 'required|string|max:255',
            'budget_alloue' => 'required|numeric|decimal:0,2|min:0|max:9999999999999.99',
            'date_debut' => 'required|date',
            'description' => 'nullable|string',
            'statut' => 'required|in:actif,en_pause,termine,archive',
            'chef_id' => ['required', 'integer', Rule::exists('users', 'id')->where('role', 'chef_chantier')->where('is_active', true)->whereNull('deleted_at')],
        ]);

        DB::transaction(function () use ($validated, $chantier) {
            $chantier->update([
                'nom' => $validated['nom'],
                'lieu' => $validated['lieu'],
                'budget_alloue' => $validated['budget_alloue'],
                'date_debut' => $validated['date_debut'],
                'description' => $validated['description'] ?? null,
                'statut' => $validated['statut'],
            ]);

            $chantier->chefsChantier()->sync([$validated['chef_id'] => ['affecte_le' => now()]]);
        });

        return redirect()->route('chantiers.index')->with('success', 'Chantier modifié avec succès.');
    }

    public function destroy(Chantier $chantier)
    {
        $chantier->delete();

        return redirect()->route('chantiers.index')->with('success', 'Chantier supprimé avec succès.');
    }
}
