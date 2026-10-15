<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

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
            'password' => ['required', 'string', 'max:1024', Password::min(12)->mixedCase()->numbers()->symbols(), 'confirmed'],
            'role' => 'required|string|in:administrateur,comptable,chef_chantier,directeur',
            'phone' => 'nullable|string|max:20',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => true,
        ]);
        $this->audit->log('user.created', 'Compte utilisateur créé.', auth()->user(), $user, $user, null, ['role' => $user->role]);

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

        $oldValues = $user->only(['name', 'email', 'role', 'phone']);
        $user->update($validated);
        $this->audit->log('user.updated', 'Profil ou rôle utilisateur modifié.', auth()->user(), $user, $user, $oldValues, $user->only(['name', 'email', 'role', 'phone']));

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
        $this->audit->log('user.active_toggled', $user->is_active ? 'Compte activé.' : 'Compte désactivé.', auth()->user(), $user, $user, null, ['is_active' => $user->is_active]);
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
            'password' => ['required', 'string', 'max:1024', Password::min(12)->mixedCase()->numbers()->symbols(), 'confirmed'],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
            'remember_token' => Str::random(60),
        ])->save();
        $user->tokens()->delete();
        $this->audit->log('password.admin_reset', 'Mot de passe réinitialisé par un administrateur.', auth()->user(), $user, $user);

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

        $this->audit->log('user.deleted', 'Compte utilisateur supprimé.', auth()->user(), $user, $user);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }
}
