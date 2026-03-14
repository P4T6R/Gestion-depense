<?php

namespace Database\Factories;

use App\Models\UniteMesure;
use Illuminate\Database\Eloquent\Factories\Factory;

class UniteMesureFactory extends Factory
{
    protected $model = UniteMesure::class;

    private static $units = ['kg', 'gramme', 'litre', 'millilitre', 'metre', 'centimetre', 'piece', 'm2', 'm3'];
    private static $index = 0;

    public function definition()
    {
        $unit = self::$units[self::$index % count(self::$units)];
        $symbol = match($unit) {
            'kg' => 'kg',
            'gramme' => 'g',
            'litre' => 'l',
            'millilitre' => 'ml',
            'metre' => 'm',
            'centimetre' => 'cm',
            'piece' => 'pc',
            'm2' => 'm²',
            'm3' => 'm³',
        };
        self::$index++;
        
        return [
            'nom' => $unit,
            'symbole' => $symbol,
        ];
    }
}
