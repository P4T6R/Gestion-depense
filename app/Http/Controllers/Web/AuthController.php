<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'max:1024'],
        ]);

        if (Auth::attempt($credentials + ['is_active' => true])) {
            $request->session()->regenerate();
            $user = $request->user();

            if ($user->two_factor_confirmed_at && $user->two_factor_secret) {
                Auth::logout();
                $request->session()->regenerate();
                $request->session()->put('two_factor_login_user_id', $user->id);

                return redirect()->route('two-factor.challenge');
            }

            $this->audit->log('auth.login', 'Connexion réussie.', $user, $user);

            if ($request->user()->must_change_password) {
                $request->session()->forget('url.intended');

                return redirect()->route('password.change.required');
            }

            return redirect()->intended('dashboard');
        }

        $target = User::query()->where('email', $credentials['email'])->first();
        $this->audit->log('auth.login_failed', 'Tentative de connexion échouée.', null, $target, $target);

        return back()->withErrors([
            'email' => 'Les identifiants fournis ne correspondent pas à nos enregistrements.',
        ])->onlyInput('email');
    }

    public function showRequiredPasswordChange(Request $request)
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.change-password');
    }

    public function updateRequiredPassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => [
                'required',
                'string',
                'max:1024',
                'different:current_password',
                Password::min(12)->mixedCase()->numbers()->symbols(),
                'confirmed',
            ],
        ], [
            'current_password.current_password' => 'Le mot de passe temporaire est incorrect.',
            'password.different' => 'Choisissez un mot de passe différent du mot de passe temporaire.',
        ]);

        $user = $request->user();
        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->save();
        $user->tokens()->delete();
        $this->audit->log('password.first_change', 'Mot de passe temporaire remplacé.', $user, $user, $user);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('dashboard')->with('success', 'Votre mot de passe personnel a été enregistré.');
    }

    public function logout(Request $request)
    {
        $this->audit->log('auth.logout', 'Déconnexion.', $request->user(), $request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
