<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepenseHistorique extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'version', 'action', 'statut_avant', 'statut_apres', 'acteur_id',
        'acteur_nom', 'acteur_role', 'motif', 'instantane',
    ];

    protected $casts = [
        'instantane' => 'array',
        'created_at' => 'datetime',
    ];

    public function depense()
    {
        return $this->belongsTo(Depense::class);
    }
}
