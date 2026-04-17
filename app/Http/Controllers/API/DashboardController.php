<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Chantier;
use App\Models\Depense;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Dashboard global (Admin, Comptable, Directeur)
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Chef de chantier : rediriger vers ses chantiers
        if ($user->isChefChantier()) {
            return $this->dashboardChef($user);
        }

        // Dashboard global pour Admin/Comptable/Directeur
        $stats = [
            // Chantiers
            'total_chantiers' => Chantier::count(),
            'chantiers_actifs' => Chantier::where('statut', 'actif')->count(),
            'chantiers_termines' => Chantier::where('statut', 'termine')->count(),

            // Budgets
            'budget_total_alloue' => Chantier::sum('budget_alloue'),
            'budget_total_consomme' => Chantier::sum('budget_consomme'),
            'budget_total_restant' => Chantier::sum(DB::raw('budget_alloue - budget_consomme')),

            // Dépenses
            'total_depenses' => Depense::count(),
            'depenses_en_attente' => Depense::where('statut_validation', 'en_attente')->count(),
            'depenses_validees' => Depense::where('statut_validation', 'validee')->count(),
            'depenses_rejetees' => Depense::where('statut_validation', 'rejetee')->count(),

            // Montants
            'montant_total_valide' => Depense::where('statut_validation', 'validee')->sum('prix_total'),
            'montant_en_attente' => Depense::where('statut_validation', 'en_attente')->sum('prix_total'),

            // Utilisateurs
            'total_utilisateurs' => User::count(),
            'chefs_chantier_actifs' => User::where('role', 'chef_chantier')->where('is_active', true)->count(),
        ];

        // Chantiers avec alertes budgétaires
        $chantiersAlerte = Chantier::select('id', 'nom', 'budget_alloue', 'budget_consomme')
            ->get()
            ->filter(function ($chantier) {
                return $chantier->pourcentage_consomme >= 70;
            })
            ->map(function ($chantier) {
                return [
                    'id' => $chantier->id,
                    'nom' => $chantier->nom,
                    'budget_alloue' => $chantier->budget_alloue,
                    'budget_consomme' => $chantier->budget_consomme,
                    'pourcentage_consomme' => $chantier->pourcentage_consomme,
                    'statut_budget' => $chantier->statut_budget,
                ];
            })
            ->values();

        // Dépenses récentes
        $depensesRecentes = Depense::with(['chantier', 'utilisateur', 'categorie'])
            ->latest()
            ->take(10)
            ->get();

        // Dépenses par catégorie
        $depensesParCategorie = Depense::select('categorie_id', DB::raw('SUM(prix_total) as total'))
            ->where('statut_validation', 'validee')
            ->groupBy('categorie_id')
            ->with('categorie')
            ->get();

        return response()->json([
            'stats' => $stats,
            'chantiers_alerte' => $chantiersAlerte,
            'depenses_recentes' => $depensesRecentes,
            'depenses_par_categorie' => $depensesParCategorie,
        ]);
    }

    /**
     * Dashboard pour chef de chantier
     */
    private function dashboardChef(User $chef)
    {
        $chantiers = $chef->chantiers()->with(['depenses'])->get();

        $stats = [
            'mes_chantiers' => $chantiers->count(),
            'mes_depenses_total' => $chef->depenses()->count(),
            'mes_depenses_en_attente' => $chef->depenses()->where('statut_validation', 'en_attente')->count(),
            'mes_depenses_validees' => $chef->depenses()->where('statut_validation', 'validee')->count(),
            'mes_depenses_rejetees' => $chef->depenses()->where('statut_validation', 'rejetee')->count(),
        ];

        $chantiersDetails = $chantiers->map(function ($chantier) {
            return [
                'id' => $chantier->id,
                'nom' => $chantier->nom,
                'budget_alloue' => $chantier->budget_alloue,
                'budget_consomme' => $chantier->budget_consomme,
                'budget_restant' => $chantier->budget_restant,
                'pourcentage_consomme' => $chantier->pourcentage_consomme,
                'statut_budget' => $chantier->statut_budget,
            ];
        });

        $depensesRecentes = $chef->depenses()
            ->with(['chantier', 'categorie', 'unite'])
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'mes_chantiers' => $chantiersDetails,
            'mes_depenses_recentes' => $depensesRecentes,
        ]);
    }

    /**
     * Statistiques d'un mois spécifique
     */
    public function statsParMois(Request $request)
    {
        $validated = $request->validate([
            'mois' => 'required|integer|between:1,12',
            'annee' => 'required|integer|min:2020',
        ]);

        $depenses = Depense::whereMonth('created_at', $validated['mois'])
            ->whereYear('created_at', $validated['annee'])
            ->where('statut_validation', 'validee')
            ->get();

        $stats = [
            'total_depenses' => $depenses->count(),
            'montant_total' => $depenses->sum('prix_total'),
            'par_categorie' => $depenses->groupBy('categorie_id')->map(function ($items) {
                return [
                    'count' => $items->count(),
                    'total' => $items->sum('prix_total'),
                ];
            }),
        ];

        return response()->json($stats);
    }
}