<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesUnitesSeeder extends Seeder
{
    public function run(): void
    {
        // Désactiver les contraintes de clés étrangères
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Vider les tables avant de les remplir
        DB::table('categories_depenses')->truncate();
        DB::table('unites_mesure')->truncate();

        // Réactiver les contraintes
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Catégories de dépenses
        $categories = [
            ['nom' => 'Matériaux construction', 'code' => 'MAT', 'description' => 'Ciment, fer, sable, etc.'],
            ['nom' => 'Main d\'œuvre', 'code' => 'MO', 'description' => 'Salaires ouvriers, maçons, etc.'],
            ['nom' => 'Location matériel', 'code' => 'LOC', 'description' => 'Pelle, grue, bétonnière, etc.'],
            ['nom' => 'Carburant', 'code' => 'CARB', 'description' => 'Essence, gasoil'],
            ['nom' => 'Fournitures', 'code' => 'FOUR', 'description' => 'Outils, équipements'],
            ['nom' => 'Transport', 'code' => 'TRANS', 'description' => 'Livraison, déplacement'],
            ['nom' => 'Sous-traitance', 'code' => 'ST', 'description' => 'Entreprises externes'],
            ['nom' => 'Divers', 'code' => 'DIV', 'description' => 'Autres dépenses'],
        ];

        foreach ($categories as $cat) {
            DB::table('categories_depenses')->insert([
                'nom' => $cat['nom'],
                'code' => $cat['code'],
                'description' => $cat['description'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Unités de mesure
        $unites = [
            ['nom' => 'Sac', 'symbole' => 'sac'],
            ['nom' => 'Tonne', 'symbole' => 't'],
            ['nom' => 'Mètre cube', 'symbole' => 'm³'],
            ['nom' => 'Mètre carré', 'symbole' => 'm²'],
            ['nom' => 'Mètre linéaire', 'symbole' => 'm'],
            ['nom' => 'Unité', 'symbole' => 'u'],
            ['nom' => 'Kilogramme', 'symbole' => 'kg'],
            ['nom' => 'Litre', 'symbole' => 'L'],
            ['nom' => 'Palette', 'symbole' => 'pal'],
            ['nom' => 'Carton', 'symbole' => 'ctn'],
            ['nom' => 'Lot', 'symbole' => 'lot'],
            ['nom' => 'Forfait', 'symbole' => 'fft'],
            ['nom' => 'Heure', 'symbole' => 'h'],
            ['nom' => 'Jour', 'symbole' => 'j'],
        ];

        foreach ($unites as $unite) {
            DB::table('unites_mesure')->insert([
                'nom' => $unite['nom'],
                'symbole' => $unite['symbole'],
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('[OK] Catégories et unités créées avec succès !');
    }
}