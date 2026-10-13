<?php

namespace App\Support;

class Amount
{
    public static function multiply(string $quantity, string $unitPrice): string
    {
        $product = bcmul($quantity, $unitPrice, 4);

        // Arrondi au centime, identique à celui des colonnes DECIMAL MySQL.
        return bcadd($product, bccomp($product, '0', 4) < 0 ? '-0.005' : '0.005', 2);
    }

    public static function sum(iterable $amounts): string
    {
        $total = '0.00';
        foreach ($amounts as $amount) {
            $total = bcadd($total, (string) $amount, 2);
        }

        return $total;
    }
}
