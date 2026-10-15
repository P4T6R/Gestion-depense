<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    /**
     * Liste des utilisateurs (Admin uniquement)
     */
    public function index(Request $request)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $query = User::query();

        // Filtres
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $users = $query->latest()->get();

        return response()->json($users);
    }

    /**
     * Créer un utilisateur (Admin uniquement)
     */
    public function store(Request $request)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => ['required', 'string', 'max:1024', Password::min(12)->mixedCase()->numbers()->symbols()],
            'role' => 'required|in:administrateur,chef_chantier,comptable,directeur',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'must_change_password' => true,
            'role' => $validated['role'],
            'phone' => $validated['phone'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user' => $user,
        ], 201);
    }

    /**
     * Détails d'un utilisateur
     */
    public function show(Request $request, User $user)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $user->load(['chantiers', 'depenses']);

        return response()->json($user);
    }

    /**
     * Modifier un utilisateur (Admin uniquement)
     */
    public function update(Request $request, User $user)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
            'role' => 'sometimes|in:administrateur,chef_chantier,comptable,directeur',
            'phone' => 'sometimes|nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
            'password' => ['sometimes', 'string', 'max:1024', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        // Hash du mot de passe si fourni
        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
            $validated['must_change_password'] = true;
            $user->setRememberToken(Str::random(60));
            $user->tokens()->delete();
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Utilisateur mis à jour avec succès',
            'user' => $user,
        ]);
    }

    /**
     * Désactiver/Activer un utilisateur (Admin uniquement)
     */
    public function toggleActive(Request $request, User $user)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $message = $user->is_active ? 'Utilisateur activé' : 'Utilisateur désactivé';

        return response()->json([
            'message' => $message,
            'user' => $user,
        ]);
    }

    /**
     * Supprimer un utilisateur (Admin uniquement - Soft delete)
     */
    public function destroy(Request $request, User $user)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Ne pas supprimer soi-même
        if ($user->id === $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas vous supprimer vous-même',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès',
        ]);
    }

    /**
     * Liste des chefs de chantier disponibles
     */
    public function chefsChantier(Request $request)
    {
        if (! $request->user()->isAdministrateur()) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $chefs = User::where('role', 'chef_chantier')
            ->where('is_active', true)
            ->get();

        return response()->json($chefs);
    }
}
