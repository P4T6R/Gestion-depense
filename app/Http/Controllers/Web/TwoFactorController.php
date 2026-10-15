<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\TotpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TotpService $totp,
        private readonly AuditLogger $audit,
    ) {}

    public function settings(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['administrateur', 'comptable'], true), 403);
        $user = $request->user();

        return view('auth.two-factor-settings', [
            'user' => $user,
            'provisioningUri' => $user->two_factor_secret && ! $user->two_factor_confirmed_at
                ? $this->totp->provisioningUri($user->two_factor_secret, $user->email)
                : null,
            'recoveryCodes' => $request->session()->pull('two_factor_recovery_codes'),
        ]);
    }

    public function enable(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['administrateur', 'comptable'], true), 403);
        $request->validate(['current_password' => ['required', 'current_password:web']]);
        $request->user()->forceFill([
            'two_factor_secret' => $this->totp->generateSecret(),
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('success', 'Secret créé. Saisissez-le dans votre application puis confirmez un code.');
    }

    public function confirm(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();
        abort_unless(in_array($user->role, ['administrateur', 'comptable'], true) && $user->two_factor_secret, 403);

        if (! $this->totp->verify($user->two_factor_secret, $validated['code'])) {
            return back()->withErrors(['code' => 'Le code est invalide ou expiré.']);
        }

        $plainCodes = collect(range(1, 8))->map(fn () => Str::upper(Str::random(5).'-'.Str::random(5)))->all();
        $user->forceFill([
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => array_map(fn ($code) => Hash::make($code), $plainCodes),
        ])->save();
        $request->session()->flash('two_factor_recovery_codes', $plainCodes);
        $this->audit->log('two_factor.enabled', 'Double authentification activée.', $user, $user, $user);

        return back()->with('success', 'Double authentification activée. Conservez les codes de secours.');
    }

    public function disable(Request $request)
    {
        $request->validate(['current_password' => ['required', 'current_password:web']]);
        $user = $request->user();
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();
        $this->audit->log('two_factor.disabled', 'Double authentification désactivée.', $user, $user, $user);

        return back()->with('success', 'Double authentification désactivée.');
    }

    public function challengeForm(Request $request)
    {
        abort_unless($request->session()->has('two_factor_login_user_id'), 404);

        return view('auth.two-factor-challenge');
    }

    public function challenge(Request $request)
    {
        $validated = $request->validate(['code' => ['required', 'string', 'max:32']]);
        $user = User::query()->where('is_active', true)->findOrFail($request->session()->get('two_factor_login_user_id'));
        $code = Str::upper(trim($validated['code']));
        $valid = $this->totp->verify((string) $user->two_factor_secret, $code);

        if (! $valid) {
            $codes = $user->two_factor_recovery_codes ?? [];
            foreach ($codes as $index => $hash) {
                if (Hash::check($code, $hash)) {
                    unset($codes[$index]);
                    $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();
                    $valid = true;
                    break;
                }
            }
        }

        if (! $valid) {
            $this->audit->log('two_factor.failed', 'Échec de double authentification.', null, $user, $user);

            return back()->withErrors(['code' => 'Le code est invalide ou expiré.']);
        }

        $request->session()->forget('two_factor_login_user_id');
        Auth::login($user);
        $request->session()->regenerate();
        $this->audit->log('auth.login', 'Connexion réussie avec double authentification.', $user, $user);

        return $user->must_change_password
            ? redirect()->route('password.change.required')
            : redirect()->intended(route('dashboard'));
    }
}
