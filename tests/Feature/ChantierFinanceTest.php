<?php

namespace Tests\Feature;

use App\Models\Chantier;
use App\Models\Depense;
use App\Models\User;
use App\Services\ChantierFinancialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChantierFinanceTest extends TestCase
{
    use RefreshDatabase;

    private function expense(Chantier $chantier, User $creator, string $status, int $amount): Depense
    {
        return Depense::factory()->create([
            'chantier_id' => $chantier->id,
            'user_id' => $creator->id,
            'statut_validation' => $status,
            'prix_total' => $amount,
            'quantite' => 1,
            'prix_unitaire' => $amount,
        ]);
    }

    private function payment(string $amount, string $reference): array
    {
        return [
            'montant' => $amount,
            'date_paiement' => '2026-10-01',
            'mode' => 'virement',
            'reference' => $reference,
        ];
    }

    public function test_only_validated_invoices_can_be_paid_and_partial_payments_never_exceed_invoice(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $accountant = User::factory()->comptable()->create();
        $chantier = Chantier::factory()->create(['budget_alloue' => 1000]);
        $pending = $this->expense($chantier, $chef, 'en_attente', 100);
        $validated = $this->expense($chantier, $chef, 'validee', 300);

        $this->actingAs($chef)->postJson('/api/depenses/'.$validated->id.'/paiements', $this->payment('100', 'V-1'))->assertForbidden();
        $this->actingAs($accountant)->postJson('/api/depenses/'.$pending->id.'/paiements', $this->payment('100', 'V-2'))->assertForbidden();
        $this->postJson('/api/depenses/'.$validated->id.'/paiements', $this->payment('100', 'V-1'))
            ->assertCreated()->assertJsonPath('paiement.montant', '100.00');
        $this->assertSame('non_paye', $validated->fresh()->statut_paiement);
        $this->postJson('/api/depenses/'.$validated->id.'/paiements', $this->payment('201', 'V-3'))
            ->assertUnprocessable()->assertJsonValidationErrors('montant');
        $this->postJson('/api/depenses/'.$validated->id.'/paiements', $this->payment('100', 'V-1'))
            ->assertUnprocessable()->assertJsonValidationErrors('reference');
        $this->postJson('/api/depenses/'.$validated->id.'/paiements', $this->payment('200', 'V-4'))->assertCreated();
        $this->assertSame('paye', $validated->fresh()->statut_paiement);
        $this->postJson('/api/depenses/'.$validated->id.'/paiements', $this->payment('1', 'V-5'))
            ->assertUnprocessable()->assertJsonValidationErrors('montant');
        $this->assertDatabaseCount('paiements', 2);

        $finance = app(ChantierFinancialService::class)->forChantier($chantier);
        $this->assertSame('100.00', $finance['factures_en_attente']);
        $this->assertSame('300.00', $finance['depenses_validees']);
        $this->assertSame('300.00', $finance['montant_paye']);
        $this->assertSame('700.00', $finance['solde_disponible']);
    }

    public function test_cancelled_payment_is_audited_and_removed_from_paid_total(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $accountant = User::factory()->comptable()->create();
        $chantier = Chantier::factory()->create(['budget_alloue' => 500]);
        $invoice = $this->expense($chantier, $chef, 'validee', 200);
        $this->actingAs($accountant)->postJson('/api/depenses/'.$invoice->id.'/paiements', $this->payment('200', 'V-10'))->assertCreated();
        $payment = $invoice->paiements()->firstOrFail();
        $this->postJson('/api/depenses/'.$invoice->id.'/paiements/'.$payment->id.'/annuler', ['motif_annulation' => 'Référence bancaire erronée'])
            ->assertOk();
        $this->assertSame('non_paye', $invoice->fresh()->statut_paiement);
        $this->assertSame('0.00', app(ChantierFinancialService::class)->forChantier($chantier)['montant_paye']);
        $this->assertSame(['etat_initial', 'paiement', 'annulation_paiement'], $invoice->historique()->pluck('action')->all());
        $this->assertSame('Référence bancaire erronée', $payment->fresh()->motif_annulation);
        $this->postJson('/api/depenses/'.$invoice->id.'/paiements/'.$payment->id.'/annuler', ['motif_annulation' => 'Encore une fois'])
            ->assertUnprocessable();
    }

    public function test_legacy_paid_flag_needs_explicit_reconciliation_before_counting_as_paid(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $accountant = User::factory()->comptable()->create();
        $chantier = Chantier::factory()->create(['budget_alloue' => 500]);
        $invoice = $this->expense($chantier, $chef, 'validee', 200);
        $invoice->update(['statut_paiement' => 'paye']);

        $finance = app(ChantierFinancialService::class)->forChantier($chantier);
        $this->assertSame('0.00', $finance['montant_paye']);
        $this->assertSame('200.00', $finance['historique_a_rapprocher']);
        $this->actingAs($accountant)->postJson('/api/depenses/'.$invoice->id.'/paiements', $this->payment('200', 'OLD-1'))
            ->assertUnprocessable()->assertJsonValidationErrors('reprise_historique');
        $this->postJson('/api/depenses/'.$invoice->id.'/paiements', $this->payment('200', 'OLD-1') + ['reprise_historique' => true])
            ->assertCreated()->assertJsonPath('paiement.reprise_historique', true);
        $finance = app(ChantierFinancialService::class)->forChantier($chantier);
        $this->assertSame('200.00', $finance['montant_paye']);
        $this->assertSame('0.00', $finance['historique_a_rapprocher']);
    }

    public function test_director_sees_five_distinct_amounts_for_each_site_and_accountant_can_open_details(): void
    {
        $chef = User::factory()->chefChantier()->create();
        $director = User::factory()->create(['role' => 'directeur']);
        $accountant = User::factory()->comptable()->create();
        $first = Chantier::factory()->create(['nom' => 'Projet Alpha', 'budget_alloue' => 1000]);
        $second = Chantier::factory()->create(['nom' => 'Projet Beta', 'budget_alloue' => 500]);
        $first->affecterChef($chef);
        $pending = $this->expense($first, $chef, 'en_attente', 100);
        $this->expense($first, $chef, 'acceptee', 50);
        $approved = $this->expense($first, $chef, 'validee', 300);
        $this->expense($first, $chef, 'rejetee', 900);
        $this->expense($second, $chef, 'validee', 200);
        $this->actingAs($accountant)->postJson('/api/depenses/'.$approved->id.'/paiements', $this->payment('120', 'BANK-42'))->assertCreated();

        $firstFinance = app(ChantierFinancialService::class)->forChantier($first);
        $this->assertSame('150.00', $firstFinance['factures_en_attente']);
        $this->assertSame('300.00', $firstFinance['depenses_validees']);
        $this->assertSame('120.00', $firstFinance['montant_paye']);
        $this->assertSame('180.00', $firstFinance['reste_a_payer']);
        $this->assertSame('700.00', $firstFinance['solde_disponible']);
        $this->get(route('chantiers.show', $first))->assertOk()->assertSee('Situation financière')->assertSee('Réellement payé')->assertSee('BANK-42');
        $this->getJson('/api/chantiers/'.$first->id.'/statistiques')
            ->assertOk()->assertJsonPath('factures_en_attente', '150.00')->assertJsonPath('montant_paye', '120.00');

        $this->actingAs($director)->get('/dashboard')->assertOk()->assertSee('Situation financière des chantiers')->assertSee('Projet Alpha');
        $this->get(route('chantiers.index'))->assertOk()->assertSee('Solde disponible')->assertSee('Projet Beta');
        $this->getJson('/api/dashboard')->assertOk()->assertJsonPath('finances.budget_alloue', '1500.00')->assertJsonPath('finances.montant_paye', '120.00');
        $this->actingAs($chef)->get(route('chantiers.show', $second))->assertForbidden();
        $this->assertSame('en_attente', $pending->fresh()->statut_validation);
    }
}
