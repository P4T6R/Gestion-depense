<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Chantier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'lieu',
        'description',
        'budget_alloue',
        'budget_consomme',
        'date_debut',
        'date_fin',
        'statut',
        'created_by',
    ];

    protected $casts = [
        'budget_alloue' => 'decimal:2',
        'budget_consomme' => 'decimal:2',
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    // Ajouter ces attributs calculés automatiquement au JSON
    protected $appends = [
        'budget_restant',
        'pourcentage_consomme',
        'statut_budget',
    ];

    // ========== RELATIONS ==========

    public function createur()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function chefsChantier()
    {
        return $this->belongsToMany(User::class, 'chantier_user')
            ->withTimestamps()
            ->withPivot('affecte_le');
    }

    public function depenses()
    {
        return $this->hasMany(Depense::class);
    }

    public function depensesValidees()
    {
        return $this->hasMany(Depense::class)->where('statut_validation', 'validee');
    }

    public function depensesEnAttente()
    {
        return $this->hasMany(Depense::class)->where('statut_validation', 'en_attente');
    }

    // ========== ATTRIBUTS CALCULÉS (Accesseurs) ==========

    // Budget restant = alloué - consommé
    public function getBudgetRestantAttribute(): float
    {
        return $this->budget_alloue - $this->budget_consomme;
    }

    // Pourcentage du budget consommé
    public function getPourcentageConsommeAttribute(): float
    {
        if ($this->budget_alloue == 0) {
            return 0;
        }

        return round(($this->budget_consomme / $this->budget_alloue) * 100, 2);
    }

    // Statut du budget (sain, attention, critique, dépassement)
    public function getStatutBudgetAttribute(): string
    {
        $pourcentage = $this->pourcentage_consomme;

        if ($pourcentage >= 100) {
            return 'depassement';
        } elseif ($pourcentage >= 90) {
            return 'critique';
        } elseif ($pourcentage >= 70) {
            return 'attention';
        } else {
            return 'sain';
        }
    }

    // ========== MÉTHODES ==========

    // Recalculer le budget consommé (après validation dépense)
    public function updateBudgetConsomme(): void
    {
        $totalValidees = $this->depensesValidees()->sum('prix_total');
        $this->update(['budget_consomme' => $totalValidees]);
    }

    // Affecter un chef de chantier
    public function affecterChef(User $chef): void
    {
        if (! $chef->isChefChantier()) {
            throw new \Exception('Seul un chef de chantier peut être affecté');
        }

        $this->chefsChantier()->syncWithoutDetaching([$chef->id => [
            'affecte_le' => now(),
        ]]);
    }

    // Retirer un chef de chantier
    public function retirerChef(User $chef): void
    {
        $this->chefsChantier()->detach($chef->id);
    }
}
