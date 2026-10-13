<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Fournisseur extends Model
{
    protected $fillable = ['nom', 'nom_normalise', 'telephone', 'email', 'adresse', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public static function normaliser(string $nom): string
    {
        return preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii(Str::squish($nom))));
    }

    public function depenses()
    {
        return $this->hasMany(Depense::class);
    }
}
