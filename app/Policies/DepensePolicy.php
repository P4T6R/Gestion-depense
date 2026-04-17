<?php

namespace App\Policies;

use App\Models\Depense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepensePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Tous les utilisateurs actifs peuvent voir la liste des dépenses
        return $user->is_active;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Depense $depense): bool
    {
        // Admin, Directeur et Comptable peuvent voir toutes les dépenses
        if (in_array($user->role, ['administrateur', 'directeur', 'comptable'])) {
            return true;
        }

        // Chef de chantier ne peut voir que ses propres dépenses
        if ($user->role === 'chef_chantier') {
            return $depense->user_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Seuls le chef de chantier et le directeur peuvent créer des dépenses
        return in_array($user->role, ['chef_chantier', 'directeur']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Depense $depense): bool
    {
        // Seul le créateur peut modifier sa dépense
        if ($depense->user_id !== $user->id) {
            return false;
        }

        // Seulement si elle est encore en attente
        if ($depense->statut_validation !== 'en_attente') {
            return false;
        }

        // Et seulement si c'est un chef de chantier ou directeur
        return in_array($user->role, ['chef_chantier', 'directeur']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Depense $depense): bool
    {
        // Mêmes règles que pour la modification
        return $this->update($user, $depense);
    }

    /**
     * Determine whether the user can approve the expense (Directeur).
     */
    public function approve(User $user, Depense $depense): bool
    {
        // Seul le directeur peut approuver
        if ($user->role !== 'directeur') {
            return false;
        }

        // La dépense doit être en attente
        return $depense->statut_validation === 'en_attente';
    }

    /**
     * Determine whether the user can validate the expense (Comptable).
     */
    public function validate(User $user, Depense $depense): bool
    {
        // Seul le comptable peut valider
        if ($user->role !== 'comptable') {
            return false;
        }

        // La dépense doit avoir été acceptée par le directeur
        return $depense->statut_validation === 'acceptee';
    }

    /**
     * Determine whether the user can reject the expense.
     */
    public function reject(User $user, Depense $depense): bool
    {
        // Le directeur peut rejeter les dépenses en attente
        if ($user->role === 'directeur' && $depense->statut_validation === 'en_attente') {
            return true;
        }

        // Le comptable peut rejeter les dépenses acceptées
        if ($user->role === 'comptable' && $depense->statut_validation === 'acceptee') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Depense $depense): bool
    {
        // Seul l'admin peut restaurer
        return $user->role === 'administrateur';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Depense $depense): bool
    {
        // Seul l'admin peut supprimer définitivement
        return $user->role === 'administrateur';
    }
}
