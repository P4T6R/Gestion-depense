<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($response = $this->rejectSessionUsingAnOldPassword($request)) {
            return $response;
        }

        $this->storePasswordFingerprint($request);

        if (! $request->user()?->must_change_password || $this->isAllowedPasswordRoute($request)) {
            return tap($next($request), fn () => $this->storePasswordFingerprint($request));
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Vous devez remplacer votre mot de passe temporaire avant de continuer.',
                'code' => 'password_change_required',
            ], 403);
        }

        return redirect()->route('password.change.required');
    }

    private function rejectSessionUsingAnOldPassword(Request $request): ?Response
    {
        if (! $request->hasSession() || ! $request->user()) {
            return null;
        }

        $storedUserId = $request->session()->get('authenticated_user_id');
        $storedFingerprint = $request->session()->get('password_fingerprint');
        if ((int) $storedUserId !== $request->user()->getKey() || ! is_string($storedFingerprint)) {
            return null;
        }

        if (hash_equals($this->passwordFingerprint($request), $storedFingerprint)) {
            return null;
        }

        Auth::guard('web')->logoutCurrentDevice();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Votre session a expiré après une modification du mot de passe.',
                'code' => 'password_changed',
            ], 401);
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Votre mot de passe a été modifié. Reconnectez-vous avec le nouveau mot de passe.',
        ]);
    }

    private function storePasswordFingerprint(Request $request): void
    {
        if (! $request->hasSession() || ! $request->user()) {
            return;
        }

        $request->session()->put([
            'authenticated_user_id' => $request->user()->getKey(),
            'password_fingerprint' => $this->passwordFingerprint($request),
        ]);
    }

    private function passwordFingerprint(Request $request): string
    {
        return hash_hmac('sha256', $request->user()->getAuthPassword(), (string) config('app.key'));
    }

    private function isAllowedPasswordRoute(Request $request): bool
    {
        return $request->routeIs(
            'password.change.required',
            'password.change.required.update',
            'logout',
        ) || $request->is(
            'api/auth/change-password',
            'api/auth/logout',
            'api/auth/me',
        );
    }
}
