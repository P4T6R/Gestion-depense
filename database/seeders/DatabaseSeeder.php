<?php

namespace Database\Seeders;

use App\Models\Chantier;
use App\Models\Depense;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Les comptes de démonstration ne doivent pas être créés en production.');
        }

        // Créer les catégories et unités d'abord
        $this->call(CategoriesUnitesSeeder::class);
        $admin = User::firstOrCreate(['email' => 'admin@chantier.com'], [
            'name' => 'Admin Principal',
            'email' => 'admin@chantier.com',
            'password' => Hash::make('password'),
            'role' => 'administrateur',
            'phone' => '+226 70 00 00 00',
            'is_active' => true,
        ]);

        // Créer un comptable
        $comptable = User::firstOrCreate(['email' => 'comptable@chantier.com'], [
            'name' => 'Marie Comptable',
            'email' => 'comptable@chantier.com',
            'password' => Hash::make('password'),
            'role' => 'comptable',
            'phone' => '+226 70 11 11 11',
            'is_active' => true,
        ]);

        // Créer un directeur
        $directeur = User::firstOrCreate(['email' => 'directeur@chantier.com'], [
            'name' => 'Pierre Directeur',
            'email' => 'directeur@chantier.com',
            'password' => Hash::make('password'),
            'role' => 'directeur',
            'phone' => '+226 70 22 22 22',
            'is_active' => true,
        ]);

        // Créer des chefs de chantier
        $chef1 = User::firstOrCreate(['email' => 'chef1@chantier.com'], [
            'name' => 'Jean Chef',
            'email' => 'chef1@chantier.com',
            'password' => Hash::make('password'),
            'role' => 'chef_chantier',
            'phone' => '+226 70 33 33 33',
            'is_active' => true,
        ]);

        $chef2 = User::firstOrCreate(['email' => 'chef2@chantier.com'], [
            'name' => 'Paul Chef',
            'email' => 'chef2@chantier.com',
            'password' => Hash::make('password'),
            'role' => 'chef_chantier',
            'phone' => '+226 70 44 44 44',
            'is_active' => true,
        ]);

        // Créer des chantiers
        $chantier1 = Chantier::firstOrCreate(['nom' => 'Construction Immeuble Centre-Ville'], [
            'nom' => 'Construction Immeuble Centre-Ville',
            'lieu' => 'Ouagadougou, Secteur 15',
            'description' => 'Construction immeuble R+3',
            'budget_alloue' => 50000000,
            'date_debut' => now()->subMonths(2),
            'date_fin' => now()->addMonths(6),
            'statut' => 'actif',
            'created_by' => $admin->id,
        ]);

        $chantier2 = Chantier::firstOrCreate(['nom' => 'Réhabilitation Route Nationale'], [
            'nom' => 'Réhabilitation Route Nationale',
            'lieu' => 'Bobo-Dioulasso - Banfora',
            'description' => 'Réhabilitation 50 km',
            'budget_alloue' => 120000000,
            'date_debut' => now()->subMonths(1),
            'date_fin' => now()->addMonths(10),
            'statut' => 'actif',
            'created_by' => $admin->id,
        ]);

        // Affecter les chefs
        $chantier1->affecterChef($chef1);
        $chantier2->affecterChef($chef2);

        // Créer des dépenses
        $depense1 = Depense::firstOrCreate(['chantier_id' => $chantier1->id, 'id_produit' => 'CIM-001'], [
            'chantier_id' => $chantier1->id,
            'user_id' => $chef1->id,
            'id_produit' => 'CIM-001',
            'nom_produit' => 'Ciment Portland',
            'unite_id' => \App\Models\UniteMesure::where('symbole', 'sac')->firstOrFail()->id, // Sac
            'quantite' => 100,
            'prix_unitaire' => 5500,
            'prix_total' => 550000,
            'categorie_id' => \App\Models\CategorieDepense::where('code', 'MAT')->firstOrFail()->id, // Matériaux
            'observations' => 'Livraison mardi',
            'statut_paiement' => 'paye',
            'statut_livraison' => 'non_livre',
            'statut_validation' => 'validee',
            'valide_par' => $comptable->id,
            'valide_le' => now()->subDays(2),
        ]);

        // Mettre à jour budgets
        $chantier1->updateBudgetConsomme();

        $this->command->info('[OK] Données de test créées !');
        $this->command->info('');
        $this->command->info('Comptes disponibles :');
        $this->command->info('[USER] admin@chantier.com / password');
        $this->command->info('[USER] comptable@chantier.com / password');
        $this->command->info('[USER] directeur@chantier.com / password');
        $this->command->info('[USER] chef1@chantier.com / password');
        $this->command->info('[USER] chef2@chantier.com / password');
    }
}
