<?php

namespace Tests\Feature;

use App\Models\CategorieDepense;
use App\Models\Chantier;
use App\Models\Depense;
use App\Models\UniteMesure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiExpenseTest extends TestCase
{
    use RefreshDatabase;

    private $admin;

    private $chef;

    private $comptable;

    private $chantier;

    private $unite;

    private $categorie;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les données de test
        $this->admin = User::factory()->admin()->create();
        $this->chef = User::factory()->chefChantier()->create();
        $this->comptable = User::factory()->comptable()->create();

        $this->chantier = Chantier::factory()->create();
        $this->chantier->chefsChantier()->attach($this->chef->id);

        $this->unite = UniteMesure::factory()->create();
        $this->categorie = CategorieDepense::factory()->create();
    }

    /**
     * Test: Authentification requise
     */
    public function test_authentication_required()
    {
        $response = $this->getJson('/api/depenses');
        $response->assertStatus(401);
    }

    /**
     * Test: Chef peut lister ses dépenses
     */
    public function test_chef_can_list_expenses()
    {
        $response = $this->actingAs($this->chef)
            ->getJson('/api/depenses');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'chantier_id',
                        'nom_produit',
                        'quantite',
                        'prix_unitaire',
                        'prix_total',
                    ],
                ],
            ]);
    }

    /**
     * Test: Créer une dépense sans fichiers
     */
    public function test_create_expense_without_files()
    {
        $response = $this->actingAs($this->chef)
            ->postJson('/api/depenses', [
                'chantier_id' => $this->chantier->id,
                'nom_produit' => 'Ciment',
                'unite_id' => $this->unite->id,
                'quantite' => 50,
                'prix_unitaire' => 5000,
                'categorie_id' => $this->categorie->id,
                'observations' => 'Test expense',
                'statut_paiement' => 'non_paye',
                'statut_livraison' => 'livre',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'depense' => [
                    'id',
                    'nom_produit',
                    'quantite',
                    'prix_total',
                ],
            ]);

        $this->assertDatabaseHas('depenses', [
            'nom_produit' => 'Ciment',
            'user_id' => $this->chef->id,
        ]);
    }

    /**
     * Test: Créer une dépense avec fichiers
     */
    public function test_create_expense_with_files()
    {
        Storage::fake('private');

        // Utiliser un fichier PDF au lieu d'une image (pas besoin d'extension GD)
        $file = UploadedFile::fake()->create('facture.pdf', 100, 'application/pdf');

        $response = $this->actingAs($this->chef)
            ->postJson('/api/depenses', [
                'chantier_id' => $this->chantier->id,
                'nom_produit' => 'Acier',
                'unite_id' => $this->unite->id,
                'quantite' => 10,
                'prix_unitaire' => 15000,
                'categorie_id' => $this->categorie->id,
                'observations' => 'Matériau de construction',
                'statut_paiement' => 'non_paye',
                'statut_livraison' => 'livre',
                'justificatifs' => [$file],
            ]);

        // Si erreur 500, afficher le contenu pour déboguer
        if ($response->status() === 500) {
            $this->fail('500 error: '.$response->getContent());
        }

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'depense' => [
                    'id',
                    'nom_produit',
                ],
            ]);

        $expense = Depense::latest()->first();
        $this->assertNotNull($expense);
    }

    /**
     * Test: Validation requise
     */
    public function test_expense_validation_required()
    {
        $response = $this->actingAs($this->chef)
            ->postJson('/api/depenses', [
                'chantier_id' => $this->chantier->id,
                // Manquent les champs requis
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'nom_produit',
                'unite_id',
                'quantite',
                'prix_unitaire',
                'categorie_id',
            ]);
    }

    /**
     * Test: Afficher une dépense
     */
    public function test_show_expense()
    {
        $depense = Depense::factory()->create([
            'user_id' => $this->chef->id,
            'chantier_id' => $this->chantier->id,
        ]);

        $response = $this->actingAs($this->chef)
            ->getJson('/api/depenses/'.$depense->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $depense->id,
                'nom_produit' => $depense->nom_produit,
            ]);
    }

    /**
     * Test: Modifier une dépense
     */
    public function test_update_expense()
    {
        $depense = Depense::factory()->create([
            'user_id' => $this->chef->id,
            'chantier_id' => $this->chantier->id,
        ]);

        $response = $this->actingAs($this->chef)
            ->putJson('/api/depenses/'.$depense->id, [
                'nom_produit' => 'Produit modifié',
                'quantite' => 100,
                'prix_unitaire' => 5000,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Dépense mise à jour avec succès',
            ]);

        $this->assertDatabaseHas('depenses', [
            'id' => $depense->id,
            'nom_produit' => 'Produit modifié',
        ]);
    }

    /**
     * Test: Supprimer une dépense
     */
    public function test_delete_expense()
    {
        $depense = Depense::factory()->create([
            'user_id' => $this->chef->id,
            'chantier_id' => $this->chantier->id,
        ]);

        $response = $this->actingAs($this->chef)
            ->deleteJson('/api/depenses/'.$depense->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Dépense supprimée avec succès',
            ]);

        $this->assertSoftDeleted('depenses', [
            'id' => $depense->id,
        ]);
    }

    /**
     * Test: Chef ne peut pas accéder aux dépenses d'autres chefs
     */
    public function test_chef_cannot_access_other_chef_expenses()
    {
        $otherChef = User::factory()->create(['role' => 'chef_chantier']);
        $depense = Depense::factory()->create([
            'user_id' => $otherChef->id,
            'chantier_id' => $this->chantier->id,
        ]);

        $response = $this->actingAs($this->chef)
            ->getJson('/api/depenses/'.$depense->id);

        $response->assertStatus(403);
    }

    /**
     * Test: Admin peut voir toutes les dépenses
     */
    public function test_admin_can_view_all_expenses()
    {
        Depense::factory(5)->create([
            'user_id' => $this->chef->id,
            'chantier_id' => $this->chantier->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/api/depenses');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    /**
     * Test: Comptable peut valider une dépense
     */
    public function test_comptable_can_validate_expense()
    {
        $depense = Depense::factory()->create([
            'user_id' => $this->chef->id,
            'chantier_id' => $this->chantier->id,
            'statut_validation' => 'acceptee',
        ]);

        $response = $this->actingAs($this->comptable)
            ->postJson('/api/depenses/'.$depense->id.'/valider', [
                'commentaire' => 'Validé',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('depenses', [
            'id' => $depense->id,
            'statut_validation' => 'validee',
        ]);
    }
}
