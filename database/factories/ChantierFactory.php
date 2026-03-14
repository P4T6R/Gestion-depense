<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Chantier>
 */
class ChantierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nom' => $this->faker->sentence(3),
            'lieu' => $this->faker->address(),
            'description' => $this->faker->paragraph(),
            'budget_alloue' => $this->faker->numberBetween(100000, 10000000),
            'budget_consomme' => 0,
            'date_debut' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'date_fin' => $this->faker->dateTimeBetween('now', '+1 year'),
            'statut' => $this->faker->randomElement(['actif', 'en_pause', 'termine', 'archive']),
            'created_by' => \App\Models\User::factory(),
        ];
    }
}
