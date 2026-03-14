<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategorieDepense extends Model
{
    use HasFactory;

    protected $table = 'categories_depenses';

    protected $fillable = [
        'nom',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ========== RELATIONS ==========
    
    public function depenses()
    {
        return $this->hasMany(Depense::class, 'categorie_id');
    }

    // ========== SCOPES ==========
    
    public function scopeActives($query)
    {
        return $query->where('is_active', true);
    }
}