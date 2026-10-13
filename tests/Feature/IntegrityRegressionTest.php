<?php

namespace Tests\Feature;

use App\Models\CategorieDepense;
use App\Models\Chantier;
use App\Models\Depense;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IntegrityRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function expense(User $chef): Depense
    {
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);

        return Depense::factory()->create(['user_id' => $chef->id, 'chantier_id' => $chantier->id]);
    }

    public function test_uploads_are_private_and_downloads_require_expense_permissions(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        $chef = User::factory()->chefChantier()->create();
        $fixture = $this->expense($chef);
        $file = UploadedFile::fake()->createWithContent("Facture d'achat.pdf", "%PDF-1.4\nTest");
        $response = $this->actingAs($chef)->postJson('/api/depenses', [
            'chantier_id' => $fixture->chantier_id, 'nom_produit' => 'Ciment',
            'quantite' => 1, 'prix_unitaire' => 100, 'unite_id' => $fixture->unite_id,
            'categorie_id' => $fixture->categorie_id, 'statut_paiement' => 'non_paye',
            'statut_livraison' => 'livre', 'justificatifs' => [$file],
        ])->assertCreated();
        $expense = Depense::findOrFail($response->json('depense.id'));
        $piece = $expense->justificatifs()->firstOrFail();
        $this->assertSame('private', $piece->disk);
        Storage::disk('private')->assertExists($piece->chemin_fichier);
        Storage::disk('public')->assertMissing($piece->chemin_fichier);
        $this->get('/justificatifs/'.$piece->id)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->actingAs(User::factory()->chefChantier()->create())
            ->getJson('/api/justificatifs/'.$piece->id)->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->get('/justificatifs/'.$piece->id)->assertRedirect(route('login'));
    }

    public function test_legacy_document_migration_preserves_bytes_and_is_repeatable(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        $expense = $this->expense(User::factory()->chefChantier()->create());
        $path = 'justificatifs/ancien.pdf';
        Storage::disk('public')->put($path, "%PDF-1.4\nAncienne pièce");
        $piece = $expense->justificatifs()->create([
            'chemin_fichier' => $path, 'nom_fichier' => 'ancien.pdf', 'type_fichier' => 'application/pdf',
            'taille_fichier' => 25, 'disk' => 'public',
        ]);
        $this->artisan('justificatifs:protect', ['--dry-run' => true])->assertSuccessful();
        Storage::disk('public')->assertExists($path);
        $this->assertSame('public', $piece->fresh()->disk);
        $this->artisan('justificatifs:protect')->assertSuccessful();
        $this->assertSame('private', $piece->fresh()->disk);
        $this->assertSame("%PDF-1.4\nAncienne pièce", Storage::disk('private')->get($path));
        Storage::disk('public')->assertMissing($path);
        $this->artisan('justificatifs:protect')->assertSuccessful();
        Storage::disk('private')->assertExists($path);
    }

    public function test_document_migration_never_overwrites_a_different_private_file(): void
    {
        Storage::fake('public');
        Storage::fake('private');
        $expense = $this->expense(User::factory()->chefChantier()->create());
        $path = 'justificatifs/collision.pdf';
        Storage::disk('public')->put($path, 'original');
        Storage::disk('private')->put($path, 'different');
        $piece = $expense->justificatifs()->create([
            'chemin_fichier' => $path, 'nom_fichier' => 'collision.pdf', 'type_fichier' => 'application/pdf',
            'taille_fichier' => 8, 'disk' => 'public',
        ]);
        $this->artisan('justificatifs:protect')->assertFailed();
        $this->assertSame('original', Storage::disk('public')->get($path));
        $this->assertSame('different', Storage::disk('private')->get($path));
        $this->assertSame('public', $piece->fresh()->disk);
    }

    public function test_demo_seeds_preserve_existing_data_and_create_director_categories(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $expense->update(['observations' => 'À conserver']);
        $this->seed(DatabaseSeeder::class);
        $counts = [User::count(), Chantier::count(), Depense::count()];
        $this->seed(DatabaseSeeder::class);
        $this->assertSame($counts, [User::count(), Chantier::count(), Depense::count()]);
        $this->assertSame('À conserver', $expense->fresh()->observations);
        $this->assertSame(5, CategorieDepense::where('type', 'administrative')->count());
    }

    public function test_category_edit_can_disable_a_category_and_persist_its_type(): void
    {
        $category = CategorieDepense::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->put('/categories/'.$category->id, [
            'nom' => $category->nom, 'code' => $category->code,
            'type' => 'administrative', 'is_active' => 0,
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertFalse($category->fresh()->is_active);
        $this->assertSame('administrative', $category->fresh()->type);
    }

    public function test_paginated_expense_stats_cover_all_pages(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $fixture = $this->expense($chef);
        Depense::factory()->count(15)->create(['user_id' => $chef->id, 'chantier_id' => $fixture->chantier_id]);
        $this->actingAs($chef)->get('/depenses')->assertOk()
            ->assertViewHas('stats', fn ($stats) => $stats['en_attente'] === 16);
    }

    public function test_chantier_and_category_details_do_not_leak_another_chefs_expenses(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $otherChef = User::factory()->chefChantier()->create();
        $expense->chantier->affecterChef($otherChef);
        $other = Depense::factory()->create([
            'user_id' => $otherChef->id, 'chantier_id' => $expense->chantier_id,
            'categorie_id' => $expense->categorie_id,
        ]);
        $this->actingAs($chef)->getJson('/api/chantiers/'.$expense->chantier_id)
            ->assertOk()->assertJsonCount(1, 'depenses')->assertJsonPath('depenses.0.id', $expense->id);
        $this->getJson('/api/categories/'.$expense->categorie_id)
            ->assertOk()->assertJsonCount(1, 'depenses')->assertJsonPath('depenses.0.id', $expense->id);
    }

    public function test_dashboard_stats_refresh_after_a_new_expense(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->get('/dashboard')->assertOk()
            ->assertViewHas('stats', fn ($stats) => (int) $stats['total_depenses'] === 0);
        $this->expense(User::factory()->chefChantier()->create());
        $this->get('/dashboard')->assertOk()
            ->assertViewHas('stats', fn ($stats) => (int) $stats['total_depenses'] === 1);
    }

    public function test_api_logout_supports_session_authentication(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'web')->postJson('/api/auth/logout')->assertOk();
        $this->assertGuest('web');
    }

    public function test_password_must_be_a_string(): void
    {
        $this->postJson('/api/login', ['email' => 'test@example.com', 'password' => ['bad']])
            ->assertUnprocessable()->assertJsonValidationErrors('password');
    }

    public function test_expense_amount_overflow_is_a_validation_error(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $this->actingAs($chef)->putJson('/api/depenses/'.$expense->id, [
            'quantite' => '99999999.99', 'prix_unitaire' => '9999999999999.99',
        ])->assertUnprocessable()->assertJsonValidationErrors('prix_total');
    }

    public function test_csv_export_cannot_turn_a_product_name_into_a_formula(): void
    {
        $this->expense(User::factory()->chefChantier()->create())->update(['nom_produit' => '=1+1']);
        $response = $this->actingAs(User::factory()->admin()->create())->get('/depenses/rapports/export-csv')->assertOk();
        $this->assertStringContainsString("'=1+1", $response->streamedContent());
    }

    public function test_malformed_email_and_repeated_failed_logins_are_handled(): void
    {
        $this->postJson('/api/login', ['email' => ['invalid'], 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/login', ['email' => 'limited@example.com', 'password' => 'wrong'])
                ->assertUnprocessable();
        }
        $this->postJson('/api/login', ['email' => 'limited@example.com', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_invoice_metadata_can_be_updated_without_replacing_its_lines(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $response = $this->actingAs($chef)->postJson('/api/depenses/avec-lignes', [
            'chantier_id' => $expense->chantier_id,
            'lignes' => [['designation' => 'Ciment', 'quantite' => 2, 'prix_unitaire' => 100]],
        ])->assertCreated();
        $invoiceId = $response->json('depense.id');
        $this->putJson('/api/depenses/'.$invoiceId, ['fournisseur' => 'Fournisseur corrigé'])
            ->assertOk()->assertJsonPath('depense.fournisseur', 'Fournisseur corrigé')
            ->assertJsonPath('depense.statut_paiement', 'non_paye')
            ->assertJsonPath('depense.prix_total', '200.00')->assertJsonCount(1, 'depense.lignes');
    }

    public function test_invoice_category_reports_and_filters_include_lines_without_double_counting(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $expense->update(['quantite' => 1, 'prix_unitaire' => 1000, 'statut_validation' => 'validee']);
        $category = CategorieDepense::factory()->create();
        $invoice = Depense::create([
            'chantier_id' => $expense->chantier_id, 'user_id' => $chef->id,
            'prix_total' => '300.00', 'statut_validation' => 'validee',
        ]);
        $invoice->lignes()->create(['designation' => 'A', 'quantite' => 1, 'prix_unitaire' => 100, 'categorie_depense_id' => $expense->categorie_id]);
        $invoice->lignes()->create(['designation' => 'B', 'quantite' => 1, 'prix_unitaire' => 200, 'categorie_depense_id' => $category->id]);
        $this->actingAs($chef)->getJson('/api/depenses?categorie_id='.$category->id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $invoice->id);
        $this->getJson('/api/dashboard/stats-mois?mois='.now()->month.'&annee='.now()->year)
            ->assertOk()->assertJsonPath('montant_total', '1300.00')
            ->assertJsonPath('par_categorie.'.$expense->categorie_id.'.total', '1100.00')
            ->assertJsonPath('par_categorie.'.$category->id.'.total', '200.00');
    }

    public function test_line_rounding_keeps_the_invoice_total_equal_to_the_sum_of_saved_lines(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $this->actingAs($chef)->postJson('/api/depenses/avec-lignes', [
            'chantier_id' => $expense->chantier_id,
            'lignes' => [
                ['designation' => 'A', 'quantite' => '0.01', 'prix_unitaire' => '0.50'],
                ['designation' => 'B', 'quantite' => '0.01', 'prix_unitaire' => '0.50'],
            ],
        ])->assertCreated()->assertJsonPath('depense.prix_total', '0.02')
            ->assertJsonPath('depense.lignes.0.montant', '0.01')->assertJsonPath('depense.lignes.1.montant', '0.01');
    }

    public function test_failed_attachment_save_rolls_back_the_invoice_and_already_written_files(): void
    {
        Storage::fake('private');
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $count = 0;
        \App\Models\Justificatif::creating(function () use (&$count) {
            if (++$count === 2) {
                throw new \RuntimeException('Simulated database write failure');
            }
        });
        try {
            $this->actingAs($chef)->postJson('/api/depenses/avec-lignes', [
                'chantier_id' => $expense->chantier_id,
                'lignes' => [['designation' => 'A', 'quantite' => 1, 'prix_unitaire' => 100]],
                'justificatifs' => [
                    UploadedFile::fake()->createWithContent('a.pdf', "%PDF-1.4\nA"),
                    UploadedFile::fake()->createWithContent('b.pdf', "%PDF-1.4\nB"),
                ],
            ])->assertStatus(500);
            $this->assertDatabaseCount('depenses', 1);
            $this->assertDatabaseCount('lignes_depense', 0);
            $this->assertDatabaseCount('justificatifs', 0);
            $this->assertSame([], Storage::disk('private')->allFiles());
        } finally {
            \App\Models\Justificatif::flushEventListeners();
        }
    }

    public function test_chef_dashboard_loads_assigned_chantiers_without_ambiguous_columns(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $expense->chantier->update(['statut' => 'actif']);
        $this->actingAs($chef)->get('/dashboard')->assertOk()
            ->assertViewHas('chantiersActifs', fn ($items) => $items->contains('id', $expense->chantier_id));
    }

    public function test_foreign_identifiers_must_be_scalar_integers(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $expense = $this->expense($chef);
        $this->actingAs($chef)->postJson('/api/depenses/avec-lignes', [
            'chantier_id' => [$expense->chantier_id],
            'lignes' => [['designation' => 'A', 'quantite' => 1, 'prix_unitaire' => 100]],
        ])->assertUnprocessable()->assertJsonValidationErrors('chantier_id');
    }
}
