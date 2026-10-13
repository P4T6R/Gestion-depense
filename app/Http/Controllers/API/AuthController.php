<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Connexion utilisateur
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|max:1024',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        // Supprimer les anciens tokens
        $user->tokens()->delete();

        // Créer un nouveau token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user,
            'token' => $token,
        ]);
    }

    /**
     * Déconnexion utilisateur
     */
    public function logout(Request $request)
    {
        // Sanctum peut aussi authentifier par session, sans token persistant.
        $token = $request->user()->currentAccessToken();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $token->delete();
        } else {
            \Illuminate\Support\Facades\Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return response()->json([
            'message' => 'Déconnexion réussie',
        ]);
    }

    /**
     * Informations utilisateur connecté
     */
    public function me(Request $request)
    {
        $user = $request->user()->load(['chantiers']);

        return response()->json([
            'user' => $user,
        ]);
    }

    /**
     * Mise à jour du profil
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'email' => 'sometimes|email|unique:users,email,'.$user->id,
        ]);

        $user->update($validated);

        return response()->json([
            'message' => 'Profil mis à jour avec succès',
            'user' => $user,
        ]);
    }

    /**
     * Changement de mot de passe
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|max:1024',
            'new_password' => 'required|string|min:8|max:1024|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Supprimer tous les tokens sauf le courant
        $token = $user->currentAccessToken();
        $tokens = $user->tokens();
        if ($token instanceof \Laravel\Sanctum\PersonalAccessToken) {
            $tokens->where('id', '!=', $token->id);
        }
        $tokens->delete();

        return response()->json([
            'message' => 'Mot de passe modifié avec succès',
        ]);
    }
}
