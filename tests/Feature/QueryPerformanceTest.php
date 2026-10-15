<?php

namespace Tests\Feature;

use App\Models\Chantier;
use App\Models\Depense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class QueryPerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expense_index_uses_one_aggregate_query_for_all_status_totals(): void
    {
        $admin = User::factory()->admin()->create();
        Depense::factory()->count(4)->create();
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->actingAs($admin)->get(route('depenses.index'))->assertOk();

        $aggregateQueries = collect($queries)->filter(function (string $sql) {
            $sql = strtolower($sql);

            return str_contains($sql, 'statut_validation') && (str_contains($sql, 'count(') || str_contains($sql, 'sum('));
        });
        $this->assertCount(1, $aggregateQueries, 'Les statistiques de dépenses doivent être calculées par une seule requête conditionnelle.');
        $this->assertLessThanOrEqual(10, count($queries), 'La liste des dépenses exécute trop de requêtes SQL.');
    }

    public function test_admin_dashboard_reuses_financial_summaries(): void
    {
        $admin = User::factory()->admin()->create();
        $chantiers = Chantier::factory()->count(3)->create(['statut' => 'actif']);
        foreach ($chantiers as $chantier) {
            Depense::factory()->create(['chantier_id' => $chantier->id]);
        }
        $queries = [];
        DB::listen(function ($query) use (&$queries) {
            $queries[] = $query->sql;
        });

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $financialQueries = collect($queries)->filter(fn (string $sql) => str_contains($sql, 'group by `chantier_id`') || str_contains($sql, 'group by `depenses`.`chantier_id`'));
        $this->assertLessThanOrEqual(3, $financialQueries->count(), 'Les agrégats financiers des chantiers ne doivent pas être recalculés deux fois.');
        $this->assertLessThanOrEqual(14, count($queries), 'Le tableau de bord administrateur exécute trop de requêtes SQL.');
    }
}
