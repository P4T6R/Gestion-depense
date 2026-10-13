<?php

namespace Database\Factories;

use App\Models\UniteMesure;
use Illuminate\Database\Eloquent\Factories\Factory;

class UniteMesureFactory extends Factory
{
    protected $model = UniteMesure::class;

    public function definition(): array
    {
        // Un nombre arbitraire de fixtures doit respecter les index uniques de la table.
        return [
            'nom' => 'Unité '.fake()->unique()->numerify('##########'),
            'symbole' => fake()->unique()->bothify('u-##########'),
            'is_active' => true,
        ];
    }
}
