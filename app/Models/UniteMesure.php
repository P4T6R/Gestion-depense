<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UniteMesure extends Model
{
    use HasFactory;

    protected $table = 'unites_mesure';

    protected $fillable = [
        'nom',
        'symbole',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ========== RELATIONS ==========

    public function depenses()
    {
        return $this->hasMany(Depense::class, 'unite_id');
    }

    public function lignes()
    {
        return $this->hasMany(LigneDepense::class, 'unite_mesure_id');
    }

    public function isUsed(): bool
    {
        return $this->depenses()->withTrashed()->exists() || $this->lignes()->exists();
    }

    // ========== SCOPES ==========

    public function scopeActives($query)
    {
        return $query->where('is_active', true);
    }
}
