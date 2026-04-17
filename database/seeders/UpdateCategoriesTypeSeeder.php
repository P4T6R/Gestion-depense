<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateCategoriesTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Catégories ADMINISTRATIVES (pour le Directeur)
        $categoriesAdministratives = [
            'Appel d\'Offre - Achat Dossier',
            'Appel d\'Offre - Montage Dossier',
            'Enregistrement de Marché',
            'Assurances & Cautions',
            'Frais Administratifs Divers',
        ];

        // D'abord, remettre tout en 'chantier'
        DB::table('categories_depenses')->update(['type' => 'chantier']);

        // Puis marquer les catégories administratives
        DB::table('categories_depenses')
            ->whereIn('nom', $categoriesAdministratives)
            ->update(['type' => 'administrative']);

        // Afficher le résumé
        $admin = DB::table('categories_depenses')->where('type', 'administrative')->count();
        $chantier = DB::table('categories_depenses')->where('type', 'chantier')->count();
        
        $this->command->info('Types de catégories mis à jour avec succès !');
        $this->command->info("Catégories administratives: {$admin}");
        $this->command->info("Catégories de chantier: {$chantier}");
    }
}
