<?php

namespace Tests\Feature;

use App\Models\Chantier;
use App\Models\Depense;
use App\Models\Fournisseur;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoiceAuditAndDuplicatesTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(Chantier $chantier, string $number, string $supplier): array
    {
        return [
            'chantier_id' => $chantier->id,
            'numero_facture' => $number,
            'fournisseur' => $supplier,
            'date_facture' => '2026-09-01',
            'lignes' => [['designation' => 'Ciment', 'quantite' => 2, 'prix_unitaire' => 100]],
        ];
    }

    public function test_director_invoice_goes_to_accountant_without_self_approval_and_keeps_decision_history(): void
    {
        $director = User::factory()->create(['role' => 'directeur']);
        $accountant = User::factory()->comptable()->create();
        $chantier = Chantier::factory()->create();

        $this->actingAs($director)->postJson('/api/depenses/avec-lignes', $this->invoice($chantier, 'DIR-1', 'Atelier ABC'))
            ->assertCreated()->assertJsonPath('depense.circuit_validation_directe', true);
        $invoice = Depense::firstOrFail();
        $this->postJson('/api/depenses/'.$invoice->id.'/approve')->assertForbidden();
        $this->post(route('depenses.reject', $invoice), ['motif_rejet' => 'Je rejette ma facture'])->assertForbidden();
        $this->get(route('depenses.show', $invoice))->assertOk()->assertDontSee('Approuver</span>', false);
        $this->actingAs($accountant)->get('/dashboard')->assertOk()->assertSee('DIR-1');
        $this->post(route('depenses.validate', $invoice), ['action' => 'reject', 'motif_rejet' => 'Justificatif incomplet'])
            ->assertRedirect();
        $this->actingAs($director)->putJson('/api/depenses/'.$invoice->id, ['observations' => 'Document corrigé'])
            ->assertOk()->assertJsonPath('depense.statut_validation', 'en_attente');
        $this->actingAs($accountant)->postJson('/api/depenses/'.$invoice->id.'/valider')
            ->assertOk()->assertJsonPath('depense.statut_validation', 'validee');

        $this->assertSame(['creation', 'rejet', 'resoumission', 'validation'], $invoice->historique()->pluck('action')->all());
        $this->assertSame('Justificatif incomplet', $invoice->historique()->where('action', 'rejet')->first()->motif);
        $this->assertSame([1, 1, 2, 2], $invoice->historique()->pluck('version')->all());
        $this->assertSame('200.00', $chantier->fresh()->budget_consomme);
    }

    public function test_supplier_number_duplicate_is_blocked_across_spelling_variants_but_not_other_suppliers(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);
        $this->actingAs($chef)->postJson('/api/depenses/avec-lignes', $this->invoice($chantier, 'FAC-001', 'Établissements Alpha'))
            ->assertCreated();
        $this->postJson('/api/depenses/avec-lignes', $this->invoice($chantier, 'fac 001', 'Etablissements  Alpha'))
            ->assertUnprocessable()->assertJsonValidationErrors('numero_facture');
        $this->assertDatabaseCount('depenses', 1);
        $this->assertDatabaseCount('fournisseurs', 1);
        $this->postJson('/api/depenses/avec-lignes', $this->invoice($chantier, 'fac 001', 'Autre fournisseur'))
            ->assertCreated();
        $this->assertDatabaseCount('fournisseurs', 2);
        $this->assertSame('ETABLISSEMENTSALPHA', strtoupper(Fournisseur::firstOrFail()->nom_normalise));
    }

    public function test_identical_attachment_is_blocked_and_failed_submission_keeps_no_invoice_or_file(): void
    {
        Storage::fake('private');
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);
        $data = $this->invoice($chantier, 'A1', 'Fournisseur A');
        $this->actingAs($chef)->post('/depenses', $data + ['justificatif' => UploadedFile::fake()->createWithContent('a.pdf', '%PDF-document identique')])
            ->assertRedirect()->assertSessionHasNoErrors();
        $first = Depense::firstOrFail();
        $this->assertNotNull($first->justificatifs()->firstOrFail()->sha256);

        $second = $this->invoice($chantier, 'B2', 'Fournisseur B');
        $this->from('/depenses/create')->post('/depenses', $second + ['justificatif' => UploadedFile::fake()->createWithContent('b.pdf', '%PDF-document identique')])
            ->assertRedirect('/depenses/create')->assertSessionHasErrors('justificatif');
        $this->assertDatabaseCount('depenses', 1);
        $this->assertDatabaseCount('justificatifs', 1);
        $this->assertCount(1, Storage::disk('private')->files('justificatifs'));
    }

    public function test_admin_can_manage_supplier_register_and_amount_match_is_visible(): void
    {
        $admin = User::factory()->admin()->create();
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);
        $this->actingAs($admin)->post(route('fournisseurs.store'), [
            'nom' => 'Béton SARL', 'telephone' => '123', 'is_active' => '1',
        ])->assertRedirect(route('fournisseurs.index'));
        $supplier = Fournisseur::firstOrFail();
        $this->get(route('fournisseurs.index'))->assertOk()->assertSee('Béton SARL');
        $this->actingAs($chef)->get(route('fournisseurs.index'))->assertForbidden();

        $this->postJson('/api/depenses/avec-lignes', $this->invoice($chantier, 'X1', 'Beton SARL'))->assertCreated();
        $this->postJson('/api/depenses/avec-lignes', $this->invoice($chantier, 'X2', 'Béton SARL'))->assertCreated();
        $invoices = Depense::orderBy('id')->get();
        $this->assertSame($supplier->id, $invoices[0]->fournisseur_id);
        $this->get(route('depenses.show', $invoices[1]))->assertOk()->assertSee('Factures à vérifier')->assertSee('Facture #'.$invoices[0]->id);
    }

    public function test_existing_attachment_fingerprint_can_be_backfilled_before_duplicate_check(): void
    {
        Storage::fake('private');
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->affecterChef($chef);
        $this->actingAs($chef)->postJson('/api/depenses/avec-lignes', $this->invoice($chantier, 'OLD-1', 'Ancien fournisseur'))
            ->assertCreated();
        $invoice = Depense::firstOrFail();
        Storage::disk('private')->put('justificatifs/ancien.pdf', '%PDF-fichier existant');
        $file = $invoice->justificatifs()->create([
            'nom_fichier' => 'ancien.pdf', 'disk' => 'private', 'chemin_fichier' => 'justificatifs/ancien.pdf',
            'type_fichier' => 'application/pdf', 'taille_fichier' => 20,
        ]);
        $this->artisan('justificatifs:empreintes')->assertSuccessful();
        $this->assertSame(hash('sha256', '%PDF-fichier existant'), $file->fresh()->sha256);

        $this->post('/depenses', $this->invoice($chantier, 'NEW-2', 'Nouveau fournisseur') + [
            'justificatif' => UploadedFile::fake()->createWithContent('copie.pdf', '%PDF-fichier existant'),
        ])->assertSessionHasErrors('justificatif');
        $this->assertDatabaseCount('depenses', 1);
    }
}
