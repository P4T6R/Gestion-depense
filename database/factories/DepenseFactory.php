<?php

namespace Database\Factories;

use App\Models\CategorieDepense;
use App\Models\Depense;
use App\Models\UniteMesure;
use Illuminate\Database\Eloquent\Factories\Factory;

class DepenseFactory extends Factory
{
    protected $model = Depense::class;

    public function definition()
    {
        $quantite = $this->faker->numberBetween(1, 100);
        $prixUnitaire = $this->faker->numberBetween(1000, 100000);

        return [
            'chantier_id' => \App\Models\Chantier::factory(),
            'user_id' => \App\Models\User::factory(),
            'id_produit' => $this->faker->bothify('PROD-####'),
            'nom_produit' => $this->faker->word(),
            'unite_id' => UniteMesure::factory(),
            'quantite' => $quantite,
            'prix_unitaire' => $prixUnitaire,
            'prix_total' => $quantite * $prixUnitaire,
            'categorie_id' => CategorieDepense::factory(),
            'observations' => $this->faker->sentence(),
            'statut_paiement' => 'non_paye',
            'statut_livraison' => $this->faker->randomElement(['livre', 'non_livre']),
            'statut_validation' => 'en_attente',
        ];
    }
}
