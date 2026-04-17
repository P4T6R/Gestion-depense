<?php

namespace App\Services;

use App\Models\Depense;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DepenseService
{
    /**
     * Créer une nouvelle dépense avec justificatif
     */
    public function create(array $data, $justificatif = null): Depense
    {
        DB::beginTransaction();
        try {
            // Calculer le prix total
            $data['prix_total'] = $data['quantite'] * $data['prix_unitaire'];
            $data['statut_validation'] = 'en_attente';
            $data['statut_paiement'] = 'non_paye';
            $data['statut_livraison'] = 'non_livre';

            $depense = Depense::create($data);

            // Gérer l'upload du justificatif si présent
            if ($justificatif) {
                $this->uploadJustificatif($depense, $justificatif);
            }

            DB::commit();
            return $depense;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mettre à jour une dépense
     */
    public function update(Depense $depense, array $data, $justificatif = null): Depense
    {
        DB::beginTransaction();
        try {
            // Recalculer le prix total
            $data['prix_total'] = $data['quantite'] * $data['prix_unitaire'];

            $depense->update($data);

            // Gérer l'upload du nouveau justificatif si présent
            if ($justificatif) {
                // Supprimer l'ancien justificatif
                $this->deleteJustificatifs($depense);
                // Ajouter le nouveau
                $this->uploadJustificatif($depense, $justificatif);
            }

            DB::commit();
            return $depense->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Approuver une dépense (Directeur)
     */
    public function approve(Depense $depense): Depense
    {
        $depense->update([
            'statut_validation' => 'acceptee',
        ]);

        return $depense;
    }

    /**
     * Valider une dépense (Comptable)
     */
    public function validate(Depense $depense, int $userId): Depense
    {
        $depense->update([
            'statut_validation' => 'validee',
            'valide_par' => $userId,
            'valide_le' => now(),
        ]);

        return $depense;
    }

    /**
     * Rejeter une dépense
     */
    public function reject(Depense $depense, string $motif, int $userId): Depense
    {
        $depense->update([
            'statut_validation' => 'rejetee',
            'motif_rejet' => $motif,
            'valide_par' => $userId,
            'valide_le' => now(),
        ]);

        return $depense;
    }

    /**
     * Supprimer une dépense avec ses justificatifs
     */
    public function delete(Depense $depense): bool
    {
        DB::beginTransaction();
        try {
            // Supprimer les justificatifs
            $this->deleteJustificatifs($depense);

            // Supprimer la dépense
            $depense->delete();

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Upload un justificatif pour une dépense
     */
    protected function uploadJustificatif(Depense $depense, $file): void
    {
        $nomFichier = time() . '_' . $file->getClientOriginalName();
        $chemin = $file->storeAs('justificatifs', $nomFichier, 'public');

        $depense->justificatifs()->create([
            'nom_fichier' => $nomFichier,
            'chemin_fichier' => $chemin,
            'type_fichier' => $file->getMimeType(),
            'taille_fichier' => $file->getSize(),
        ]);
    }

    /**
     * Supprimer tous les justificatifs d'une dépense
     */
    protected function deleteJustificatifs(Depense $depense): void
    {
        foreach ($depense->justificatifs as $justificatif) {
            // Supprimer le fichier physique
            if (Storage::disk('public')->exists($justificatif->chemin_fichier)) {
                Storage::disk('public')->delete($justificatif->chemin_fichier);
            }
            // Supprimer l'enregistrement
            $justificatif->delete();
        }
    }
}
