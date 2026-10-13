<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Justificatif extends Model
{
    use HasFactory;

    protected $table = 'justificatifs';

    protected $fillable = [
        'depense_id',
        'disk',
        'nom_fichier',
        'chemin_fichier',
        'type_fichier',
        'taille_fichier',
    ];

    // Relations
    public function depense()
    {
        return $this->belongsTo(Depense::class);
    }
}
