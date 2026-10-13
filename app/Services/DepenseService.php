<?php

namespace App\Services;

use App\Models\Chantier;
use App\Models\Depense;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class DepenseService
{
    public function create(array $data, User $user, array $files = []): Depense
    {
        Gate::forUser($user)->authorize('create', Depense::class);
        $data['user_id'] = $user->id;

        return $this->persist(null, $data, $user, $files);
    }

    public function update(Depense $depense, array $data, User $user, array $files = []): Depense
    {
        return $this->persist($depense, $data, $user, $files);
    }

    private function persist(?Depense $depense, array $data, User $user, array $files): Depense
    {
        if (($data['statut_paiement'] ?? 'non_paye') === 'paye') {
            throw ValidationException::withMessages([
                'statut_paiement' => 'Une facture ne peut pas être marquée payée lors de sa saisie ou de sa modification. Elle doit d’abord être approuvée par le directeur et validée par la comptable.',
            ]);
        }

        $paths = [];
        try {
            return DB::transaction(function () use ($depense, $data, $user, $files, &$paths) {
                $lignes = $data['lignes'] ?? null;
                $wasRejected = false;
                $beforeCorrection = null;
                unset($data['lignes'], $data['justificatif'], $data['justificatifs'], $data['prix_total'], $data['statut_validation'], $data['valide_par'], $data['valide_le'], $data['motif_rejet']);
                if ($depense) {
                    unset($data['user_id']);
                }
                $chantierId = $data['chantier_id'] ?? $depense?->chantier_id;
                Chantier::findOrFail($chantierId);
                abort_unless($user->hasAccessToChantier($chantierId), 403);
                if ($lignes !== null) {
                    $data = array_diff_key($data, array_flip([
                        'nom_produit', 'quantite', 'prix_unitaire', 'categorie_id', 'unite_id',
                    ]));
                    $data += array_fill_keys(['nom_produit', 'quantite', 'prix_unitaire', 'categorie_id', 'unite_id'], null);
                    $data['prix_total'] = 0;
                    if (! $depense) {
                        $data['date_facture'] ??= now()->toDateString();
                    }
                }
                if ($depense) {
                    Chantier::whereIn('id', array_unique([$depense->chantier_id, $data['chantier_id'] ?? $depense->chantier_id]))->orderBy('id')->lockForUpdate()->get();
                    $depense = Depense::query()->lockForUpdate()->findOrFail($depense->id);
                    Gate::forUser($user)->authorize('update', $depense);
                    $wasRejected = $depense->statut_validation === 'rejetee';
                    $beforeCorrection = $wasRejected ? $this->correctionState($depense) : null;
                    $depense->update($data);
                } else {
                    $data['statut_validation'] = 'en_attente';
                    $data['statut_paiement'] = 'non_paye';
                    $data['statut_livraison'] ??= 'non_livre';
                    $depense = Depense::create($data);
                }
                if ($lignes !== null) {
                    $depense->lignes()->delete();
                    foreach ($lignes as $ligne) {
                        $depense->lignes()->create($ligne);
                    }
                    $depense->recalculerTotal();
                }
                if ($wasRejected && $files === [] && $beforeCorrection === $this->correctionState($depense)) {
                    throw ValidationException::withMessages([
                        'correction' => 'Modifiez la facture ou ajoutez un justificatif avant de la soumettre à nouveau.',
                    ]);
                }
                foreach ($files as $file) {
                    $paths[] = $this->uploadJustificatif($depense, $file);
                }

                if ($wasRejected) {
                    $depense->update([
                        'statut_validation' => 'en_attente',
                        'motif_rejet' => null,
                        'valide_par' => null,
                        'valide_le' => null,
                    ]);
                }

                return $depense->fresh(['lignes.categorieDepense', 'lignes.uniteMesure', 'justificatifs', 'categorie', 'unite']);
            });
        } catch (\Throwable $exception) {
            // Une transaction SQL ne peut pas annuler les fichiers déjà écrits.
            Storage::disk('private')->delete($paths);
            throw $exception;
        }
    }

    private function correctionState(Depense $depense): array
    {
        $fields = [
            'chantier_id', 'numero_facture', 'fournisseur', 'date_facture', 'id_produit',
            'nom_produit', 'unite_id', 'quantite', 'prix_unitaire', 'prix_total',
            'categorie_id', 'observations', 'statut_livraison',
        ];
        $lineFields = ['designation', 'categorie_depense_id', 'unite_mesure_id', 'quantite', 'prix_unitaire', 'montant', 'observations'];

        $fresh = $depense->fresh();
        $facture = $fresh->only($fields);
        $facture['date_facture'] = $fresh->date_facture?->toDateString();

        return [
            'facture' => $facture,
            'lignes' => $depense->lignes()->orderBy('id')->get()->map(fn ($line) => $line->only($lineFields))->all(),
        ];
    }

    private function uploadJustificatif(Depense $depense, UploadedFile $file): string
    {
        $path = $file->store('justificatifs', 'private');
        if ($path === false) {
            throw new \RuntimeException('Impossible de sauvegarder le justificatif.');
        }
        try {
            $depense->justificatifs()->create([
                'nom_fichier' => $file->getClientOriginalName(),
                'disk' => 'private',
                'chemin_fichier' => $path,
                'type_fichier' => $file->getMimeType(),
                'taille_fichier' => $file->getSize(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('private')->delete($path);
            throw $exception;
        }

        return $path;
    }

    public function approve(Depense $depense, User $user): Depense
    {
        return $this->transition($depense, $user, 'approve', ['statut_validation' => 'acceptee']);
    }

    public function validate(Depense $depense, User $user): Depense
    {
        return $this->transition($depense, $user, 'validate', [
            'statut_validation' => 'validee', 'valide_par' => $user->id,
            'valide_le' => now(), 'motif_rejet' => null,
        ]);
    }

    public function reject(Depense $depense, string $motif, User $user): Depense
    {
        if (trim($motif) === '') {
            throw ValidationException::withMessages(['motif_rejet' => 'Un motif de rejet est obligatoire.']);
        }

        return $this->transition($depense, $user, 'reject', [
            'statut_validation' => 'rejetee', 'valide_par' => $user->id,
            'valide_le' => now(), 'motif_rejet' => $motif,
        ]);
    }

    private function transition(Depense $depense, User $user, string $ability, array $data): Depense
    {
        return DB::transaction(function () use ($depense, $user, $ability, $data) {
            // Sérialiser les validations d'un chantier évite de perdre une mise à jour du budget.
            $chantier = Chantier::query()->lockForUpdate()->findOrFail($depense->chantier_id);
            $locked = Depense::query()->lockForUpdate()->findOrFail($depense->id);
            Gate::forUser($user)->authorize($ability, $locked);
            $locked->update($data);
            if ($data['statut_validation'] === 'validee') {
                $chantier->updateBudgetConsomme();
            }

            return $locked->fresh(['validateur', 'categorie', 'unite']);
        });
    }

    public function delete(Depense $depense, User $user): bool
    {
        return DB::transaction(function () use ($depense, $user) {
            $locked = Depense::query()->lockForUpdate()->findOrFail($depense->id);
            Gate::forUser($user)->authorize('delete', $locked);

            // Le soft delete conserve les pièces pour l'audit et une éventuelle restauration.
            return (bool) $locked->delete();
        });
    }
}
