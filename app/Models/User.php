<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $attributes = ['is_active' => true];

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // ========== RELATIONS ==========

    // Chantiers affectés (pour les chefs)
    public function chantiers()
    {
        return $this->belongsToMany(Chantier::class, 'chantier_user')
            ->withTimestamps()
            ->withPivot('affecte_le');
    }

    // Dépenses créées
    public function depenses()
    {
        return $this->hasMany(Depense::class);
    }

    // Dépenses validées (pour comptable)
    public function depensesValidees()
    {
        return $this->hasMany(Depense::class, 'valide_par');
    }

    // ========== MÉTHODES DE VÉRIFICATION RÔLE ==========

    public function isAdministrateur(): bool
    {
        return $this->role === 'administrateur';
    }

    public function isChefChantier(): bool
    {
        return $this->role === 'chef_chantier';
    }

    public function isComptable(): bool
    {
        return $this->role === 'comptable';
    }

    public function isDirecteur(): bool
    {
        return $this->role === 'directeur';
    }

    // Vérifier l'accès à un chantier
    public function hasAccessToChantier($chantierId): bool
    {
        if (! $this->is_active) {
            return false;
        }

        // Admin, Comptable, Directeur : accès à tous les chantiers
        if ($this->isAdministrateur() || $this->isComptable() || $this->isDirecteur()) {
            return true;
        }

        // Chef de chantier : uniquement ses chantiers affectés
        if ($this->isChefChantier()) {
            return $this->chantiers()->where('chantiers.id', $chantierId)->exists();
        }

        return false;
    }
}
