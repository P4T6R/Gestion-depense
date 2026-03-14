<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Chantier;
use App\Models\Depense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user() ?? User::where('role', 'administrateur')->first();

        if (!$user) {
            return view('dashboard', [
                'stats' => $this->getEmptyStats(),
                'depensesRecentes' => collect(),
                'chantiersActifs' => collect(),
                'userRole' => null
            ]);
        }

        try {
            $userRole = $user->role;
            
            // Cache des statistiques pour 5 minutes
            $cacheKey = "dashboard_stats_{$userRole}_{$user->id}";
            $stats = Cache::remember($cacheKey, 300, function() use ($userRole, $user) {
                switch ($userRole) {
                    case 'administrateur':
                        return $this->getAdminStats();
                    case 'chef_chantier':
                        return $this->getChefStats($user);
                    case 'directeur':
                        return $this->getDirecteurStats($user);
                    case 'comptable':
                        return $this->getComptableStats();
                    default:
                        return $this->getEmptyStats();
                }
            });
            
            $depensesRecentes = collect();
            $chantiersActifs = collect();

            // Dashboard personnalisé selon le rôle
            switch ($userRole) {
                case 'administrateur':
                    $depensesRecentes = Depense::with(['chantier:id,nom', 'utilisateur:id,name'])->latest()->take(5)->get();
                    $chantiersActifs = Chantier::where('statut', 'actif')
                        ->select('id', 'nom', 'lieu', 'budget_alloue', 'budget_consomme', 'statut')
                        ->withCount('depenses')
                        ->take(5)
                        ->get();
                    break;

                case 'chef_chantier':
                    $depensesRecentes = Depense::where('user_id', $user->id)->with(['chantier:id,nom'])->latest()->take(5)->get();
                    $chantiersActifs = $user->chantiers()
                        ->where('statut', 'actif')
                        ->select('id', 'nom', 'lieu', 'budget_alloue', 'budget_consomme', 'statut')
                        ->withCount('depenses')
                        ->get();
                    break;

                case 'directeur':
                    // Directeur voit TOUTES les dépenses en attente (pas seulement ses chantiers)
                    $depensesRecentes = Depense::where('statut_validation', 'en_attente')
                        ->with(['chantier:id,nom', 'utilisateur:id,name'])
                        ->latest()
                        ->take(5)
                        ->get();
                    // Directeur voit TOUS les chantiers actifs
                    $chantiersActifs = Chantier::where('statut', 'actif')
                        ->select('id', 'nom', 'lieu', 'budget_alloue', 'budget_consomme', 'statut')
                        ->withCount('depenses')
                        ->take(5)
                        ->get();
                    break;

                case 'comptable':
                    $depensesRecentes = Depense::where('statut_validation', 'acceptee')
                        ->with(['chantier:id,nom', 'utilisateur:id,name'])
                        ->latest()
                        ->take(5)
                        ->get();
                    // Comptable voit tous les chantiers actifs pour le suivi des budgets
                    $chantiersActifs = Chantier::where('statut', 'actif')
                        ->select('id', 'nom', 'lieu', 'budget_alloue', 'budget_consomme', 'statut')
                        ->withCount('depenses')
                        ->take(5)
                        ->get();
                    break;
            }

        } catch (\Exception $e) {
            $stats = $this->getEmptyStats();
            $depensesRecentes = collect();
            $chantiersActifs = collect();
            $userRole = null;
        }

        return view('dashboard', compact('stats', 'depensesRecentes', 'chantiersActifs', 'userRole'));
    }

    // Dashboard Administrateur - Statistiques globales
    private function getAdminStats()
    {
        // Optimisation: utiliser une seule requête avec selectRaw pour toutes les stats
        $stats = Depense::selectRaw("
            COUNT(*) as total_depenses,
            SUM(prix_total) as total_montant_depenses,
            SUM(CASE WHEN statut_validation = 'en_attente' THEN 1 ELSE 0 END) as depenses_en_attente,
            SUM(CASE WHEN statut_validation = 'en_attente' THEN prix_total ELSE 0 END) as montant_en_attente,
            SUM(CASE WHEN statut_validation = 'acceptee' THEN 1 ELSE 0 END) as depenses_acceptees,
            SUM(CASE WHEN statut_validation = 'acceptee' THEN prix_total ELSE 0 END) as montant_acceptees,
            SUM(CASE WHEN statut_validation = 'validee' THEN 1 ELSE 0 END) as depenses_validees,
            SUM(CASE WHEN statut_validation = 'validee' THEN prix_total ELSE 0 END) as montant_validees,
            SUM(CASE WHEN statut_validation = 'rejetee' THEN 1 ELSE 0 END) as depenses_rejetees,
            SUM(CASE WHEN statut_validation = 'rejetee' THEN prix_total ELSE 0 END) as montant_rejetees
        ")->first();

        return [
            'total_utilisateurs' => User::count(),
            'total_chantiers' => Chantier::count(),
            'total_depenses' => $stats->total_depenses ?? 0,
            'total_montant_depenses' => $stats->total_montant_depenses ?? 0,
            'depenses_en_attente' => $stats->depenses_en_attente ?? 0,
            'montant_en_attente' => $stats->montant_en_attente ?? 0,
            'depenses_acceptees' => $stats->depenses_acceptees ?? 0,
            'montant_acceptees' => $stats->montant_acceptees ?? 0,
            'depenses_validees' => $stats->depenses_validees ?? 0,
            'montant_validees' => $stats->montant_validees ?? 0,
            'depenses_rejetees' => $stats->depenses_rejetees ?? 0,
            'montant_rejetees' => $stats->montant_rejetees ?? 0,
            'total_justificatifs' => DB::table('justificatifs')->count() ?? 0,
        ];
    }

    // Dashboard Chef de Chantier - Ses dépenses et ses chantiers
    private function getChefStats($user)
    {
        // Optimisation: une seule requête au lieu de plusieurs
        $stats = Depense::where('user_id', $user->id)
            ->selectRaw("
                COUNT(*) as mes_depenses,
                SUM(prix_total) as montant_total,
                SUM(CASE WHEN statut_validation = 'en_attente' THEN 1 ELSE 0 END) as depenses_en_attente,
                SUM(CASE WHEN statut_validation = 'en_attente' THEN prix_total ELSE 0 END) as montant_en_attente,
                SUM(CASE WHEN statut_validation = 'acceptee' THEN 1 ELSE 0 END) as depenses_acceptees,
                SUM(CASE WHEN statut_validation = 'acceptee' THEN prix_total ELSE 0 END) as montant_acceptees,
                SUM(CASE WHEN statut_validation = 'validee' THEN 1 ELSE 0 END) as depenses_validees,
                SUM(CASE WHEN statut_validation = 'validee' THEN prix_total ELSE 0 END) as montant_validees,
                SUM(CASE WHEN statut_validation = 'rejetee' THEN 1 ELSE 0 END) as depenses_rejetees,
                SUM(CASE WHEN statut_validation = 'rejetee' THEN prix_total ELSE 0 END) as montant_rejetees
            ")
            ->first();
        
        return [
            'mes_depenses' => $stats->mes_depenses ?? 0,
            'montant_total' => $stats->montant_total ?? 0,
            'depenses_en_attente' => $stats->depenses_en_attente ?? 0,
            'montant_en_attente' => $stats->montant_en_attente ?? 0,
            'depenses_acceptees' => $stats->depenses_acceptees ?? 0,
            'montant_acceptees' => $stats->montant_acceptees ?? 0,
            'depenses_validees' => $stats->depenses_validees ?? 0,
            'montant_validees' => $stats->montant_validees ?? 0,
            'depenses_rejetees' => $stats->depenses_rejetees ?? 0,
            'montant_rejetees' => $stats->montant_rejetees ?? 0,
            'mes_chantiers' => $user->chantiers()->count(),
        ];
    }

    // Dashboard Directeur - Dépenses de TOUS les chantiers (directeur de la structure)
    private function getDirecteurStats($user)
    {
        // Optimisation: une seule requête pour toutes les stats
        $stats = Depense::selectRaw("
            SUM(CASE WHEN statut_validation = 'en_attente' THEN 1 ELSE 0 END) as a_approuver,
            SUM(CASE WHEN statut_validation = 'en_attente' THEN prix_total ELSE 0 END) as montant_a_approuver,
            SUM(CASE WHEN statut_validation = 'acceptee' THEN 1 ELSE 0 END) as approuvees,
            SUM(CASE WHEN statut_validation = 'acceptee' THEN prix_total ELSE 0 END) as montant_approuvees,
            SUM(CASE WHEN statut_validation = 'validee' THEN 1 ELSE 0 END) as validees,
            SUM(CASE WHEN statut_validation = 'validee' THEN prix_total ELSE 0 END) as montant_validees,
            SUM(CASE WHEN statut_validation = 'rejetee' THEN 1 ELSE 0 END) as rejetees,
            SUM(CASE WHEN statut_validation = 'rejetee' THEN prix_total ELSE 0 END) as montant_rejetees
        ")->first();

        return [
            'total_chantiers' => Chantier::count(),
            'a_approuver' => $stats->a_approuver ?? 0,
            'montant_a_approuver' => $stats->montant_a_approuver ?? 0,
            'approuvees' => $stats->approuvees ?? 0,
            'montant_approuvees' => $stats->montant_approuvees ?? 0,
            'validees' => $stats->validees ?? 0,
            'montant_validees' => $stats->montant_validees ?? 0,
            'rejetees' => $stats->rejetees ?? 0,
            'montant_rejetees' => $stats->montant_rejetees ?? 0,
        ];
    }

    // Dashboard Comptable - Dépenses à valider
    private function getComptableStats()
    {
        // Optimisation: une seule requête pour toutes les stats
        $stats = Depense::selectRaw("
            SUM(CASE WHEN statut_validation = 'acceptee' THEN 1 ELSE 0 END) as a_valider,
            SUM(CASE WHEN statut_validation = 'acceptee' THEN prix_total ELSE 0 END) as montant_a_valider,
            SUM(CASE WHEN statut_validation = 'validee' THEN 1 ELSE 0 END) as validees,
            SUM(CASE WHEN statut_validation = 'validee' THEN prix_total ELSE 0 END) as montant_validees,
            SUM(CASE WHEN statut_validation = 'rejetee' THEN 1 ELSE 0 END) as rejetees,
            SUM(CASE WHEN statut_validation = 'rejetee' THEN prix_total ELSE 0 END) as montant_rejetees
        ")->first();
        
        return [
            'a_valider' => $stats->a_valider ?? 0,
            'montant_a_valider' => $stats->montant_a_valider ?? 0,
            'validees' => $stats->validees ?? 0,
            'montant_validees' => $stats->montant_validees ?? 0,
            'rejetees' => $stats->rejetees ?? 0,
            'montant_rejetees' => $stats->montant_rejetees ?? 0,
        ];
    }

    private function getEmptyStats()
    {
        return [
            // Stats Admin
            'total_utilisateurs' => 0,
            'total_chantiers' => 0,
            'total_depenses' => 0,
            'total_montant_depenses' => 0,
            'total_justificatifs' => 0,
            
            // Stats Chef de Chantier
            'mes_chantiers' => 0,
            'mes_depenses' => 0,
            'montant_total' => 0,
            
            // Stats communes
            'depenses_en_attente' => 0,
            'montant_en_attente' => 0,
            'depenses_acceptees' => 0,
            'montant_acceptees' => 0,
            'depenses_validees' => 0,
            'montant_validees' => 0,
            'depenses_rejetees' => 0,
            'montant_rejetees' => 0,
            
            // Stats Directeur
            'a_approuver' => 0,
            'montant_a_approuver' => 0,
            'approuvees' => 0,
            'montant_approuvees' => 0,
            'validees' => 0,
            
            // Stats Comptable
            'a_valider' => 0,
            'montant_a_valider' => 0,
            'rejetees' => 0,
        ];
    }
}
