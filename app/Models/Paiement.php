<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paiement extends Model
{
    protected $fillable = [
        'montant', 'date_paiement', 'mode', 'reference', 'enregistre_par',
        'enregistre_par_nom', 'reprise_historique', 'annule_le', 'annule_par',
        'annule_par_nom', 'motif_annulation',
    ];

    protected $casts = [
        'montant' => 'decimal:2',
        'date_paiement' => 'date',
        'reprise_historique' => 'boolean',
        'annule_le' => 'datetime',
    ];

    public function depense()
    {
        return $this->belongsTo(Depense::class);
    }
}
