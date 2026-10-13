<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LigneDepense extends Model
{
    use HasFactory;

    protected $table = 'lignes_depense';

    protected $fillable = [
        'depense_id',
        'categorie_depense_id',
        'designation',
        'unite_mesure_id',
        'quantite',
        'prix_unitaire',
        'montant',
        'observations',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'prix_unitaire' => 'decimal:2',
        'montant' => 'decimal:2',
    ];

    // Calculer automatiquement le montant
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($ligne) {
            $ligne->montant = \App\Support\Amount::multiply((string) $ligne->quantite, (string) $ligne->prix_unitaire);
        });
    }

    // ========== RELATIONS ==========

    public function depense()
    {
        return $this->belongsTo(Depense::class);
    }

    public function categorieDepense()
    {
        return $this->belongsTo(CategorieDepense::class);
    }

    public function uniteMesure()
    {
        return $this->belongsTo(UniteMesure::class);
    }
}
