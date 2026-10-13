<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CategoriesUnitesSeeder extends Seeder
{
    public function run(): void
    {
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
            \App\Models\CategorieDepense::firstOrCreate(['code' => $cat['code']], [
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
            \App\Models\UniteMesure::firstOrCreate(['symbole' => $unite['symbole']], [
                'nom' => $unite['nom'],
                'symbole' => $unite['symbole'],
                'description' => null,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $administratives = [
            'AO_ACHAT' => "Appel d'Offre - Achat Dossier",
            'AO_MONTAGE' => "Appel d'Offre - Montage Dossier",
            'ENREG_MARCHE' => 'Enregistrement de Marché',
            'ASSUR_CAUT' => 'Assurances & Cautions',
            'FRAIS_ADMIN' => 'Frais Administratifs Divers',
        ];
        foreach ($administratives as $code => $nom) {
            $categorie = \App\Models\CategorieDepense::firstOrCreate(['code' => $code], [
                'nom' => $nom, 'type' => 'administrative', 'is_active' => true,
            ]);
            if ($categorie->type !== 'administrative') {
                $categorie->update(['type' => 'administrative']);
            }
        }

        $this->command->info('[OK] Catégories et unités créées avec succès !');
    }
}
