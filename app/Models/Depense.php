<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Depense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'chantier_id',
        'user_id',
        // Infos facture
        'numero_facture',
        'fournisseur',
        'date_facture',
        // Anciens champs (compatibilité)
        'id_produit',
        'nom_produit',
        'unite_id',
        'quantite',
        'prix_unitaire',
        'prix_total',
        'categorie_id',
        'observations',
        'statut_paiement',
        'statut_livraison',
        'statut_validation',
        'valide_par',
        'valide_le',
        'motif_rejet',
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'prix_unitaire' => 'decimal:2',
        'prix_total' => 'decimal:2',
        'valide_le' => 'datetime',
        'date_facture' => 'date',
    ];

    // ========== RELATIONS ==========
    
    public function chantier()
    {
        return $this->belongsTo(Chantier::class);
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'valide_par');
    }

    public function justificatifs()
    {
        return $this->hasMany(Justificatif::class);
    }

    public function categorie()
    {
        return $this->belongsTo(CategorieDepense::class, 'categorie_id');
    }

    public function unite()
    {
        return $this->belongsTo(UniteMesure::class, 'unite_id');
    }

    public function lignes()
    {
        return $this->hasMany(LigneDepense::class);
    }

    // ========== MUTATEURS (Setters) ==========
    
    // Recalculer le montant total depuis les lignes
    public function recalculerTotal(): void
    {
        $this->prix_total = $this->lignes()->sum('montant');
        $this->save();
    }
    
    // Calcul automatique du prix total (pour compatibilité avec anciennes dépenses)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($depense) {
            // Si pas de lignes et qu'on a quantité/prix_unitaire (ancien format)
            if (is_null($depense->prix_total) && $depense->quantite && $depense->prix_unitaire) {
                $depense->prix_total = $depense->quantite * $depense->prix_unitaire;
            }
        });

        static::updating(function ($depense) {
            // Recalculer si quantité ou prix unitaire change (ancien format)
            if ($depense->isDirty(['quantite', 'prix_unitaire']) && $depense->quantite && $depense->prix_unitaire) {
                $depense->prix_total = $depense->quantite * $depense->prix_unitaire;
            }
        });
    }

    // ========== MÉTHODES DE VALIDATION ==========
    
    public function valider(User $comptable): void
    {
        if (!$comptable->isComptable() && !$comptable->isAdministrateur()) {
            throw new \Exception('Seul un comptable peut valider une dépense');
        }

        $this->update([
            'statut_validation' => 'validee',
            'valide_par' => $comptable->id,
            'valide_le' => now(),
            'motif_rejet' => null,
        ]);

        // Mettre à jour le budget du chantier
        $this->chantier->updateBudgetConsomme();
    }

    public function rejeter(User $comptable, string $motif): void
    {
        if (!$comptable->isComptable() && !$comptable->isAdministrateur()) {
            throw new \Exception('Seul un comptable peut rejeter une dépense');
        }

        if (empty($motif)) {
            throw new \Exception('Un motif de rejet est obligatoire');
        }

        $this->update([
            'statut_validation' => 'rejetee',
            'valide_par' => $comptable->id,
            'valide_le' => now(),
            'motif_rejet' => $motif,
        ]);
    }

    // ========== SCOPES (Filtres) ==========
    
    public function scopeEnAttente($query)
    {
        return $query->where('statut_validation', 'en_attente');
    }

    public function scopeValidees($query)
    {
        return $query->where('statut_validation', 'validee');
    }

    public function scopeRejetees($query)
    {
        return $query->where('statut_validation', 'rejetee');
    }

    public function scopeParChantier($query, $chantierId)
    {
        return $query->where('chantier_id', $chantierId);
    }
}