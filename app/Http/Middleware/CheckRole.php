<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Vérifier que l'utilisateur est authentifié et actif
        if (!auth()->check() || !auth()->user()->is_active) {
            return abort(403, 'Accès non autorisé.');
        }

        // Vérifier si le rôle de l'utilisateur est parmi les rôles autorisés
        if (!in_array(auth()->user()->role, $roles)) {
            return abort(403, 'Vous n\'avez pas l\'autorisation d\'accéder à cette ressource.');
        }

        return $next($request);
    }
}
