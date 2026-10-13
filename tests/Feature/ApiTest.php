<?php

namespace Tests\Feature;

use App\Models\CategorieDepense;
use App\Models\Chantier;
use App\Models\UniteMesure;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;

    protected $chef;

    protected $comptable;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les utilisateurs de test
        $this->admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'role' => 'administrateur',
            'is_active' => true,
        ]);

        $this->chef = User::factory()->create([
            'name' => 'Chef Test',
            'email' => 'chef@test.com',
            'role' => 'chef_chantier',
            'is_active' => true,
        ]);

        $this->comptable = User::factory()->create([
            'name' => 'Comptable Test',
            'email' => 'comptable@test.com',
            'role' => 'comptable',
            'is_active' => true,
        ]);
    }

    public function test_login_admin()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'user',
                'token',
            ]);

        $this->token = $response->json('token');
    }

    public function test_login_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422);
    }

    public function test_get_user_profile()
    {
        $this->actingAs($this->admin, 'sanctum');

        $response = $this->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson([
                'user' => [
                    'id' => $this->admin->id,
                    'name' => 'Admin Test',
                    'email' => 'admin@test.com',
                    'role' => 'administrateur',
                ],
            ]);
    }

    public function test_admin_can_list_users()
    {
        $this->actingAs($this->admin, 'sanctum');

        $response = $this->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_chef_cannot_list_users()
    {
        $this->actingAs($this->chef, 'sanctum');

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_create_chantier()
    {
        $this->actingAs($this->admin, 'sanctum');

        $response = $this->postJson('/api/chantiers', [
            'nom' => 'Test Chantier',
            'lieu' => 'Test Location',
            'description' => 'Test Description',
            'budget_alloue' => 1000000,
            'date_debut' => '2025-01-01',
            'date_fin' => '2025-12-31',
            'statut' => 'actif',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'chantier' => [
                    'nom' => 'Test Chantier',
                    'budget_alloue' => '1000000.00',
                ],
            ]);
    }

    public function test_chef_cannot_create_chantier()
    {
        $this->actingAs($this->chef, 'sanctum');

        $response = $this->postJson('/api/chantiers', [
            'nom' => 'Test Chantier',
            'budget_alloue' => 1000000,
        ]);

        $response->assertStatus(403);
    }

    public function test_list_chantiers()
    {
        // Créer un chantier
        $chantier = Chantier::factory()->create([
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin, 'sanctum');

        $response = $this->getJson('/api/chantiers');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }

    public function test_create_depense()
    {
        $chantier = Chantier::factory()->create([
            'created_by' => $this->admin->id,
        ]);

        $chantier->affecterChef($this->chef);
        $categorie = CategorieDepense::factory()->create();
        $unite = UniteMesure::factory()->create();
        $this->actingAs($this->chef, 'sanctum');

        $response = $this->postJson('/api/depenses', [
            'chantier_id' => $chantier->id,
            'nom_produit' => 'Test Produit',
            'quantite' => 10,
            'prix_unitaire' => 1000,
            'categorie_id' => $categorie->id,
            'unite_id' => $unite->id,
            'statut_paiement' => 'non_paye',
            'statut_livraison' => 'livre',
        ]);

        $response->assertStatus(201);
    }

    public function test_dashboard_access()
    {
        $this->actingAs($this->admin, 'sanctum');

        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'stats' => [
                    'total_chantiers',
                    'chantiers_actifs',
                    'total_depenses',
                    'depenses_validees',
                ],
            ]);
    }
}
