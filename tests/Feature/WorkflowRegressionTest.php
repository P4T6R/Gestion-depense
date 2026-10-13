<?php

namespace Tests\Feature;

use App\Models\CategorieDepense;
use App\Models\Chantier;
use App\Models\Depense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkflowRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function expense(User $chef, array $attributes = []): Depense
    {
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);

        return Depense::factory()->create(array_merge([
            'user_id' => $chef->id,
            'chantier_id' => $chantier->id,
        ], $attributes));
    }

    private function invoice(Chantier $chantier): array
    {
        return [
            'chantier_id' => $chantier->id,
            'numero_facture' => 'FACT-001',
            'lignes' => [
                ['designation' => 'Ciment', 'quantite' => '2.50', 'prix_unitaire' => '100.25'],
                ['designation' => 'Transport', 'quantite' => '1', 'prix_unitaire' => '0'],
            ],
        ];
    }

    public function test_inactive_user_cannot_log_in_on_the_web(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_existing_token_and_session_cannot_access_after_deactivation(): void
    {
        $user = User::factory()->admin()->create();
        $token = $user->createToken('test')->plainTextToken;
        $user->update(['is_active' => false]);
        $this->withToken($token)->getJson('/api/dashboard')->assertForbidden();
        $this->actingAs($user, 'web')->get('/dashboard')->assertForbidden();
    }

    public function test_web_units_use_the_correct_database_table(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post('/unites', [
            'nom' => 'Boîte', 'symbole' => 'bte',
        ])->assertRedirect(route('unites.index'));
        $this->assertDatabaseHas('unites_mesure', ['nom' => 'Boîte']);
    }

    public function test_web_chantier_creation_preserves_the_author_without_optional_fields(): void
    {
        $admin = User::factory()->admin()->create();
        $chef = User::factory()->chefChantier()->create();
        $this->actingAs($admin)->post('/chantiers', [
            'nom' => 'Projet', 'lieu' => 'Ouaga', 'budget_alloue' => 100,
            'date_debut' => '2026-01-01', 'chef_id' => $chef->id,
        ])->assertRedirect(route('chantiers.index'));
        $this->assertDatabaseHas('chantiers', ['nom' => 'Projet', 'created_by' => $admin->id]);
    }

    public function test_web_chantier_can_be_paused(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->put('/chantiers/'.$chantier->id, [
            'nom' => 'Projet', 'lieu' => 'Ouaga', 'budget_alloue' => 100,
            'date_debut' => '2026-01-01', 'statut' => 'en_pause', 'chef_id' => $chef->id,
        ])->assertRedirect(route('chantiers.index'));
        $this->assertSame('en_pause', $chantier->fresh()->statut);
    }

    public function test_api_creates_and_updates_a_multi_line_invoice(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);
        $this->actingAs($chef)->postJson('/api/depenses/avec-lignes', $this->invoice($chantier))
            ->assertCreated()->assertJsonPath('depense.prix_total', '250.63');
        $depense = Depense::firstOrFail();
        $this->assertCount(2, $depense->lignes);
        $data = $this->invoice($chantier);
        $data['lignes'][0]['quantite'] = 3;
        $this->putJson('/api/depenses/'.$depense->id, $data)
            ->assertOk()->assertJsonPath('depense.prix_total', '300.75');
        $this->assertCount(2, $depense->fresh()->lignes);
    }

    public function test_web_invoice_creation_show_and_edit_work(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);
        $this->actingAs($chef)->post('/depenses', $this->invoice($chantier))
            ->assertRedirect(route('depenses.index'))->assertSessionHasNoErrors();
        $depense = Depense::firstOrFail();
        $this->get('/depenses/'.$depense->id)->assertOk()->assertSee('FACT-001')->assertSee('Ciment');
        $this->get('/depenses/'.$depense->id.'/edit')->assertOk()->assertSee('FACT-001');
        $data = $this->invoice($chantier);
        $data['lignes'][0]['prix_unitaire'] = 200;
        $this->put('/depenses/'.$depense->id, $data)->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame('500.00', $depense->fresh()->prix_total);
    }

    public function test_chef_cannot_create_or_move_expenses_on_unassigned_chantiers(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $unassigned = Chantier::factory()->create();
        $this->actingAs($chef)->post('/depenses', $this->invoice($unassigned))->assertForbidden();
        $this->put('/depenses/'.$expense->id, [
            'chantier_id' => $unassigned->id, 'nom_produit' => 'Ciment', 'quantite' => 1,
            'prix_unitaire' => 100, 'unite_id' => $expense->unite_id, 'categorie_id' => $expense->categorie_id,
        ])->assertForbidden();
        $this->assertSame($expense->chantier_id, $expense->fresh()->chantier_id);
    }

    public function test_comptable_cannot_create_an_expense_through_the_api(): void
    {
        $chantier = Chantier::factory()->create();
        $this->actingAs(User::factory()->comptable()->create())
            ->postJson('/api/depenses/avec-lignes', $this->invoice($chantier))->assertForbidden();
    }

    public function test_api_rejects_invalid_attachments_before_creating_an_expense(): void
    {
        Storage::fake('public');
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $data = [
            'chantier_id' => $expense->chantier_id, 'nom_produit' => 'Ciment', 'quantite' => 1,
            'prix_unitaire' => 100, 'unite_id' => $expense->unite_id, 'categorie_id' => $expense->categorie_id,
            'statut_paiement' => 'non_paye', 'statut_livraison' => 'livre',
            'justificatifs' => [UploadedFile::fake()->create('script.html', 10, 'text/html')],
        ];
        $this->actingAs($chef)->postJson('/api/depenses', $data)
            ->assertUnprocessable()->assertJsonValidationErrors('justificatifs.0');
        $this->assertDatabaseCount('depenses', 1);
    }

    public function test_accountant_cannot_bypass_director_approval(): void
    {
        $expense = $this->expense(User::factory()->chefChantier()->create());
        $this->actingAs(User::factory()->comptable()->create())
            ->postJson('/api/depenses/'.$expense->id.'/valider')->assertForbidden();
        $this->assertSame('en_attente', $expense->fresh()->statut_validation);
    }

    public function test_director_can_approve_or_reject_from_the_web_without_spending_budget(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $director = User::factory()->create(['role' => 'directeur']);
        $accountant = User::factory()->comptable()->create();
        $rejected = $this->expense($chef, ['prix_total' => 200]);
        $approved = $this->expense($chef, ['prix_total' => 300]);

        $this->actingAs($director)->get(route('depenses.show', $rejected))
            ->assertOk()->assertSee('Approuver')->assertSee('Rejeter')
            ->assertSee(route('depenses.reject', $rejected));
        $this->from(route('depenses.show', $rejected))
            ->post(route('depenses.reject', $rejected), ['motif_rejet' => 'Court'])
            ->assertRedirect(route('depenses.show', $rejected))->assertSessionHasErrors('motif_rejet');
        $this->assertSame('en_attente', $rejected->fresh()->statut_validation);
        $this->post(route('depenses.reject', $rejected), ['motif_rejet' => 'Justificatif illisible'])
            ->assertRedirect(route('depenses.show', $rejected));
        $this->assertSame('rejetee', $rejected->fresh()->statut_validation);
        $this->assertSame('Justificatif illisible', $rejected->fresh()->motif_rejet);
        $this->assertSame('0.00', $rejected->chantier->fresh()->budget_consomme);
        $this->actingAs($accountant)->post(route('depenses.validate', $rejected), ['action' => 'accept'])
            ->assertForbidden();

        $this->actingAs($director)->post(route('depenses.approve', $approved))
            ->assertRedirect(route('depenses.show', $approved));
        $this->assertSame('acceptee', $approved->fresh()->statut_validation);
        $this->assertSame('non_paye', $approved->fresh()->statut_paiement);
        $this->assertSame('0.00', $approved->chantier->fresh()->budget_consomme);
        $this->actingAs($chef)->post(route('depenses.reject', $approved), ['motif_rejet' => 'Motif interdit'])
            ->assertForbidden();
    }

    public function test_creator_can_correct_a_director_rejection_and_submit_it_again(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $director = User::factory()->create(['role' => 'directeur']);
        $accountant = User::factory()->comptable()->create();
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);
        $this->actingAs($chef)->postJson('/api/depenses/avec-lignes', $this->invoice($chantier))->assertCreated();
        $expense = Depense::firstOrFail();

        $this->actingAs($director)->post(route('depenses.reject', $expense), [
            'motif_rejet' => 'Corriger la quantité de ciment.',
        ])->assertRedirect();
        $this->actingAs($chef)->get(route('depenses.show', $expense))
            ->assertOk()->assertSee('Corriger et soumettre à nouveau');
        $this->get(route('depenses.edit', $expense))
            ->assertOk()->assertSee('Corriger la quantité de ciment.')
            ->assertSee('Corriger et soumettre à nouveau');
        $this->actingAs($accountant)->get(route('depenses.edit', $expense))->assertForbidden();
        $this->actingAs($chef)->deleteJson('/api/depenses/'.$expense->id)->assertForbidden();

        $this->putJson('/api/depenses/'.$expense->id, $this->invoice($chantier))
            ->assertUnprocessable()->assertJsonValidationErrors('correction');
        $this->assertSame('rejetee', $expense->fresh()->statut_validation);

        $data = $this->invoice($chantier);
        $data['lignes'][0]['quantite'] = 3;
        $this->put(route('depenses.update', $expense), $data)
            ->assertRedirect(route('depenses.show', $expense))
            ->assertSessionHas('success', 'Facture corrigée et soumise à nouveau au directeur.');
        $this->assertSame('en_attente', $expense->fresh()->statut_validation);
        $this->assertNull($expense->fresh()->motif_rejet);
        $this->assertNull($expense->fresh()->valide_par);
        $this->assertNull($expense->fresh()->valide_le);
        $this->assertSame('300.75', $expense->fresh()->prix_total);
        $this->assertSame('non_paye', $expense->fresh()->statut_paiement);
        $this->assertSame('0.00', $chantier->fresh()->budget_consomme);

        $this->actingAs($accountant)->post(route('depenses.validate', $expense), ['action' => 'accept'])->assertForbidden();
        $this->actingAs($director)->get('/dashboard')->assertOk()->assertSee('FACT-001');
        $this->post(route('depenses.approve', $expense))->assertRedirect();
        $this->assertSame('acceptee', $expense->fresh()->statut_validation);
        $this->actingAs($accountant)->post(route('depenses.validate', $expense), ['action' => 'accept'])->assertRedirect();
        $this->assertSame('validee', $expense->fresh()->statut_validation);
        $this->assertSame('300.75', $chantier->fresh()->budget_consomme);
    }

    public function test_accountant_rejection_also_returns_to_director_after_creator_correction(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $director = User::factory()->create(['role' => 'directeur']);
        $accountant = User::factory()->comptable()->create();
        $expense = $this->expense($chef, ['prix_unitaire' => 100, 'quantite' => 2, 'prix_total' => 200]);

        $this->actingAs($director)->post(route('depenses.approve', $expense))->assertRedirect();
        $this->actingAs($accountant)->post(route('depenses.validate', $expense), [
            'action' => 'reject', 'motif_rejet' => 'Le prix indiqué est incorrect.',
        ])->assertRedirect();
        $this->assertSame('rejetee', $expense->fresh()->statut_validation);
        $this->actingAs($chef)->get(route('depenses.edit', $expense))
            ->assertOk()->assertSee('Le prix indiqué est incorrect.');
        $this->putJson('/api/depenses/'.$expense->id, [])
            ->assertUnprocessable()->assertJsonValidationErrors('correction');
        $this->putJson('/api/depenses/'.$expense->id, ['prix_unitaire' => 120])
            ->assertOk()->assertJsonPath('depense.statut_validation', 'en_attente')
            ->assertJsonPath('depense.prix_total', '240.00');
        $this->assertNull($expense->fresh()->motif_rejet);
        $this->assertSame('0.00', $expense->chantier->fresh()->budget_consomme);
        $this->actingAs($accountant)->postJson('/api/depenses/'.$expense->id.'/valider')->assertForbidden();
        $this->actingAs($director)->postJson('/api/depenses/'.$expense->id.'/approve')
            ->assertOk()->assertJsonPath('depense.statut_validation', 'acceptee');
    }

    public function test_invoice_cannot_be_marked_paid_before_director_and_accountant_decisions(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);
        $data = $this->invoice($chantier);
        $data['statut_paiement'] = 'paye';
        $this->actingAs($chef)->postJson('/api/depenses/avec-lignes', $data)
            ->assertUnprocessable()->assertJsonValidationErrors('statut_paiement');
        $this->assertDatabaseCount('depenses', 0);

        unset($data['statut_paiement']);
        $this->postJson('/api/depenses/avec-lignes', $data)->assertCreated();
        $expense = Depense::firstOrFail();
        $this->putJson('/api/depenses/'.$expense->id, ['statut_paiement' => 'paye'])
            ->assertUnprocessable()->assertJsonValidationErrors('statut_paiement');
        $this->assertSame('non_paye', $expense->fresh()->statut_paiement);
    }

    public function test_director_approval_does_not_increase_the_chefs_used_budget(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef, ['prix_total' => 200]);
        $expense->chantier->update(['statut' => 'actif', 'budget_alloue' => 1000]);

        $director = User::factory()->create(['role' => 'directeur']);
        $this->actingAs($director)->post(route('depenses.approve', $expense))->assertRedirect();
        $this->actingAs($chef)->get('/dashboard')->assertOk()->assertSee('Budget Utilisé')->assertSee('0.0%');

        $accountant = User::factory()->comptable()->create();
        $this->actingAs($accountant)->post(route('depenses.validate', $expense), ['action' => 'accept'])->assertRedirect();
        $this->actingAs($chef)->get('/dashboard')->assertOk()->assertSee('20.0%');
    }

    public function test_approval_validation_and_budget_are_consistent_across_web_and_api(): void
    {
        $expense = $this->expense(User::factory()->chefChantier()->create(), ['prix_unitaire' => 100, 'quantite' => 2, 'prix_total' => 200]);
        $director = User::factory()->create(['role' => 'directeur']);
        $accountant = User::factory()->comptable()->create();
        $this->actingAs($director)->postJson('/api/depenses/'.$expense->id.'/approve')
            ->assertOk()->assertJsonPath('depense.statut_validation', 'acceptee');
        $this->actingAs($accountant)->post('/depenses/'.$expense->id.'/validate', ['action' => 'accept'])->assertRedirect();
        $this->assertSame('validee', $expense->fresh()->statut_validation);
        $this->assertSame($accountant->id, $expense->fresh()->valide_par);
        $this->assertSame('200.00', $expense->chantier->fresh()->budget_consomme);
        $this->postJson('/api/depenses/'.$expense->id.'/valider')->assertForbidden();
        $this->assertSame('200.00', $expense->chantier->fresh()->budget_consomme);
    }

    public function test_accepted_invoice_cannot_be_changed_or_deleted(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef, ['statut_validation' => 'acceptee']);
        $this->actingAs($chef)->putJson('/api/depenses/'.$expense->id, ['quantite' => 10])->assertForbidden();
        $this->deleteJson('/api/depenses/'.$expense->id)->assertForbidden();
        $this->assertNotSoftDeleted($expense);
    }

    public function test_zero_price_recalculates_total(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $this->actingAs($chef)->putJson('/api/depenses/'.$expense->id, ['prix_unitaire' => 0])
            ->assertOk()->assertJsonPath('depense.prix_total', '0.00');
    }

    public function test_monthly_stats_only_include_the_chefs_own_expenses(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $this->expense($chef, ['statut_validation' => 'validee', 'prix_total' => 100]);
        $this->expense(User::factory()->chefChantier()->create(), ['statut_validation' => 'validee', 'prix_total' => 900]);
        $this->actingAs($chef)->getJson('/api/dashboard/stats-mois?mois='.now()->month.'&annee='.now()->year)
            ->assertOk()->assertJsonPath('total_depenses', 1);
    }

    public function test_assigning_the_same_chef_twice_is_idempotent(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $this->actingAs(User::factory()->admin()->create());
        $this->postJson('/api/chantiers/'.$chantier->id.'/affecter-chef', ['user_id' => $chef->id])->assertOk();
        $this->postJson('/api/chantiers/'.$chantier->id.'/affecter-chef', ['user_id' => $chef->id])->assertOk();
        $this->assertSame(1, $chantier->chefsChantier()->count());
    }

    public function test_deleting_a_unit_used_by_a_soft_deleted_expense_returns_validation_error(): void
    {
        $expense = $this->expense(User::factory()->chefChantier()->create());
        $expense->delete();
        $this->actingAs(User::factory()->admin()->create())
            ->deleteJson('/api/unites/'.$expense->unite_id)->assertUnprocessable();
        $this->assertDatabaseHas('unites_mesure', ['id' => $expense->unite_id]);
    }

    public function test_deleting_a_category_used_in_invoice_lines_is_refused(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $category = CategorieDepense::factory()->create();
        $expense->lignes()->create(['designation' => 'Ciment', 'categorie_depense_id' => $category->id, 'quantite' => 1, 'prix_unitaire' => 100]);
        $this->actingAs(User::factory()->admin()->create())
            ->deleteJson('/api/categories/'.$category->id)->assertUnprocessable();
    }

    public function test_excel_export_is_a_valid_workbook_with_literal_text(): void
    {
        $expense = $this->expense(User::factory()->chefChantier()->create(), ['nom_produit' => '=1+1']);
        $response = $this->actingAs(User::factory()->admin()->create())->get('/depenses/rapports/export-excel');
        $response->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'export-test-');
        try {
            file_put_contents($path, $response->streamedContent());
            $zip = new \ZipArchive;
            $this->assertTrue($zip->open($path));
            $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
            $this->assertNotFalse($sheet);
            $this->assertStringContainsString('=1+1', $sheet);
            $this->assertStringNotContainsString('<f>', $sheet);
            $this->assertNotFalse(simplexml_load_string($sheet));
            $zip->close();
        } finally {
            unlink($path);
        }
    }
}
