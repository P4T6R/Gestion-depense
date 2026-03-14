<?php

namespace Database\Factories;

use App\Models\CategorieDepense;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategorieDepenseFactory extends Factory
{
    protected $model = CategorieDepense::class;

    public function definition()
    {
        return [
            'nom' => $this->faker->unique()->word(),
            'code' => strtoupper($this->faker->unique()->bothify('???')),
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
