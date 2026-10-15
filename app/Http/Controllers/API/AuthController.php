<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly TotpService $totp,
    ) {}

    /**
     * Connexion utilisateur
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|max:1024',
            'two_factor_code' => 'nullable|digits:6',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            $this->audit->log('auth.api_login_failed', 'Tentative de connexion API échouée.', null, $user, $user);
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        if (! $user->is_active) {
            return response()->json([
                'message' => 'Votre compte a été désactivé. Contactez l\'administrateur.',
            ], 403);
        }

        if ($user->two_factor_confirmed_at && ! $this->totp->verify((string) $user->two_factor_secret, (string) $request->input('two_factor_code'))) {
            $this->audit->log('two_factor.api_failed', 'Code de double authentification API absent ou invalide.', null, $user, $user);

            return response()->json([
                'message' => 'Un code de double authentification valide est requis.',
                'two_factor_required' => true,
            ], 422);
        }

        // Supprimer les anciens tokens
        $user->tokens()->delete();

        // Créer un nouveau token
        $token = $user->createToken('auth_token')->plainTextToken;
        $this->audit->log('auth.api_login', 'Connexion API réussie.', $user, $user);

        return response()->json([
            'message' => 'Connexion réussie',
            'user' => $user,
            'token' => $token,
            'password_change_required' => $user->must_change_password,
        ]);
    }

    /**
     * Déconnexion utilisateur
     */
    public function logout(Request $request)
    {
        $this->audit->log('auth.api_logout', 'Déconnexion API.', $request->user(), $request->user());
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
            'new_password' => [
                'required',
                'string',
                'max:1024',
                'different:current_password',
                Password::min(12)->mixedCase()->numbers()->symbols(),
                'confirmed',
            ],
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect',
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make($request->new_password),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();

        // Supprimer tous les tokens sauf le courant
        $token = $user->currentAccessToken();
        $tokens = $user->tokens();
        if ($token instanceof PersonalAccessToken) {
            $tokens->where('id', '!=', $token->id);
        }
        $tokens->delete();
        $this->audit->log('password.api_change', 'Mot de passe modifié depuis l’API.', $user, $user, $user);

        return response()->json([
            'message' => 'Mot de passe modifié avec succès',
            'password_change_required' => false,
        ]);
    }
}
