<?php

namespace Tests\Feature;

use App\Models\Chantier;
use App\Models\Depense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileChefExpenseTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_keeps_results_within_the_chefs_own_invoices(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $other = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->chefsChantier()->attach([$chef->id, $other->id]);
        Depense::factory()->create(['chantier_id' => $chantier->id, 'user_id' => $chef->id, 'fournisseur' => 'Ciment Ivoire']);
        Depense::factory()->create(['chantier_id' => $chantier->id, 'user_id' => $other->id, 'fournisseur' => 'Ciment Ivoire']);

        $this->actingAs($chef, 'sanctum')->getJson('/api/depenses?q=Ciment')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_rejected_invoice_accepts_multipart_correction_from_mobile(): void
    {
        Storage::fake('private');
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->chefsChantier()->attach($chef->id);
        $depense = Depense::factory()->create([
            'chantier_id' => $chantier->id,
            'user_id' => $chef->id,
            'statut_validation' => 'rejetee',
            'motif_rejet' => 'Le document est illisible.',
        ]);

        $this->actingAs($chef, 'sanctum')->post('/api/depenses/'.$depense->id, [
            '_method' => 'PUT',
            'numero_facture' => 'CORR-2026-001',
            'justificatif' => UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('depense.statut_validation', 'en_attente');

        $this->assertDatabaseHas('justificatifs', ['depense_id' => $depense->id]);
    }

    public function test_rejected_multi_line_invoice_keeps_editable_lines_on_resubmission(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $chantier = Chantier::factory()->create();
        $chantier->chefsChantier()->attach($chef->id);
        $depense = Depense::factory()->create([
            'chantier_id' => $chantier->id,
            'user_id' => $chef->id,
            'statut_validation' => 'rejetee',
            'motif_rejet' => 'Corrigez la quantité.',
        ]);
        $depense->lignes()->create(['designation' => 'Ciment', 'quantite' => 2, 'prix_unitaire' => 5000]);

        $this->actingAs($chef, 'sanctum')->post('/api/depenses/'.$depense->id, [
            '_method' => 'PUT',
            'lignes' => [['designation' => 'Ciment', 'quantite' => 3, 'prix_unitaire' => 5000]],
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('depense.statut_validation', 'en_attente');

        $this->assertDatabaseHas('lignes_depense', ['depense_id' => $depense->id, 'quantite' => 3]);
    }
}
