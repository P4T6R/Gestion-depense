<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ajouter les catégories administratives pour le directeur
        DB::table('categories_depenses')->insert([
            [
                'nom' => 'Appel d\'Offre - Achat Dossier',
                'code' => 'AO_ACHAT',
                'description' => 'Frais d\'achat de dossier d\'appel d\'offre',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Appel d\'Offre - Montage Dossier',
                'code' => 'AO_MONTAGE',
                'description' => 'Frais de montage de dossier d\'appel d\'offre',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Enregistrement de Marché',
                'code' => 'ENREG_MARCHE',
                'description' => 'Frais d\'enregistrement de marché et timbres',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Assurances & Cautions',
                'code' => 'ASSUR_CAUT',
                'description' => 'Frais d\'assurance et de caution de marché',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Frais Administratifs Divers',
                'code' => 'FRAIS_ADMIN',
                'description' => 'Autres frais administratifs liés au projet',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('categories_depenses')
            ->whereIn('nom', [
                'Appel d\'Offre - Achat Dossier',
                'Appel d\'Offre - Montage Dossier',
                'Enregistrement de Marché',
                'Assurances & Cautions',
                'Frais Administratifs Divers',
            ])
            ->delete();
    }
};
