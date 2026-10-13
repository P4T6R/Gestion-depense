<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Lister les utilisateurs (Admin seulement)
     */
    public function index()
    {
        if (auth()->user()->role !== 'administrateur') {
            abort(403, 'Seul un administrateur peut accéder à la liste des utilisateurs.');
        }

        $users = User::all();

        return view('users.index', compact('users'));
    }

    /**
     * Afficher le formulaire de création d'utilisateur (Admin seulement)
     */
    public function create()
    {
        if (auth()->user()->role !== 'administrateur') {
            abort(403, 'Seul un administrateur peut créer un utilisateur.');
        }

        return view('users.create');
    }

    /**
     * Enregistrer un nouvel utilisateur (Admin seulement)
     */
    public function store(Request $request)
    {
        if (auth()->user()->role !== 'administrateur') {
            abort(403, 'Seul un administrateur peut créer un utilisateur.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:administrateur,comptable,chef_chantier,directeur',
            'phone' => 'nullable|string|max:20',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Afficher le formulaire d'édition d'utilisateur (Admin seulement)
     */
    public function edit(User $user)
    {
        if (auth()->user()->role !== 'administrateur') {
            abort(403, 'Seul un administrateur peut modifier un utilisateur.');
        }

        return view('users.edit', compact('user'));
    }

    /**
     * Mettre à jour un utilisateur (Admin seulement)
     */
    public function update(Request $request, User $user)
    {
        if (auth()->user()->role !== 'administrateur') {
            abort(403, 'Seul un administrateur peut modifier un utilisateur.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,'.$user->id,
            'role' => 'required|string|in:administrateur,comptable,chef_chantier,directeur',
            'phone' => 'nullable|string|max:20',
        ]);

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'Utilisateur modifié avec succès.');
    }

    /**
     * Activer/Désactiver un utilisateur (Admin seulement)
     */
    public function toggleActive(User $user)
    {
        if (auth()->user()->role !== 'administrateur') {
            abort(403, 'Seul un administrateur peut désactiver un utilisateur.');
        }

        $user->update(['is_active' => ! $user->is_active]);
        $message = $user->is_active ? 'Utilisateur activé.' : 'Utilisateur désactivé.';

        return redirect()->route('users.index')->with('success', $message);
    }

    /**
     * Afficher le formulaire de réinitialisation du mot de passe (Admin seulement)
     */
    public function resetPassword(User $user)
    {
        if (auth()->user()->role !== 'administrateur') {
            abort(403, 'Seul un administrateur peut réinitialiser un mot de passe.');
        }

        return view('users.reset-password', compact('user'));
    }

    /**
     * Mettre à jour le mot de passe (Admin seulement)
     */
    public function updatePassword(Request $request, User $user)
    {
        if (auth()->user()->role !== 'administrateur') {
            abort(403, 'Seul un administrateur peut réinitialiser un mot de passe.');
        }

        $validated = $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('users.index')->with('success', 'Mot de passe modifié avec succès.');
    }

    /**
     * Supprimer un utilisateur (Admin seulement)
     */
    public function destroy(User $user)
    {
        if (auth()->user()->role !== 'administrateur') {
            abort(403, 'Seul un administrateur peut supprimer un utilisateur.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }
}
