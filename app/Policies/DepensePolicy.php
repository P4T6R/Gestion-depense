<?php

namespace App\Policies;

use App\Models\Depense;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DepensePolicy
{
    use HandlesAuthorization;

    public function before(User $user): ?bool
    {
        return $user->is_active ? null : false;
    }

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
            return $depense->user_id === $user->id && $user->hasAccessToChantier($depense->chantier_id);
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

        // Une facture rejetée peut être corrigée puis renvoyée au directeur.
        if (! in_array($depense->statut_validation, ['en_attente', 'rejetee'])) {
            return false;
        }

        // Et seulement si c'est un chef de chantier ou directeur
        return in_array($user->role, ['chef_chantier', 'directeur']) && $user->hasAccessToChantier($depense->chantier_id);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Depense $depense): bool
    {
        return $depense->statut_validation === 'en_attente' && $this->update($user, $depense);
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
        return $depense->statut_validation === 'en_attente'
            && ! $depense->circuit_validation_directe
            && $depense->user_id !== $user->id;
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

        // La facture du directeur va directement à la comptable ; les autres exigent l'approbation.
        return $depense->statut_validation === 'acceptee'
            || ($depense->statut_validation === 'en_attente' && $depense->circuit_validation_directe);
    }

    /**
     * Determine whether the user can reject the expense.
     */
    public function reject(User $user, Depense $depense): bool
    {
        // Le directeur peut rejeter les dépenses en attente
        if ($user->role === 'directeur' && $depense->statut_validation === 'en_attente' && ! $depense->circuit_validation_directe && $depense->user_id !== $user->id) {
            return true;
        }

        // Le comptable peut rejeter les dépenses acceptées
        if ($user->role === 'comptable') {
            return $depense->statut_validation === 'acceptee'
                || ($depense->statut_validation === 'en_attente' && $depense->circuit_validation_directe);
        }

        return false;
    }

    public function recordPayment(User $user, Depense $depense): bool
    {
        return $user->role === 'comptable' && $depense->statut_validation === 'validee';
    }

    public function cancelPayment(User $user, Depense $depense): bool
    {
        return $user->role === 'comptable' && $depense->statut_validation === 'validee';
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
