<?php

namespace App\Services;

use App\Models\Chantier;
use App\Models\Depense;
use App\Models\Fournisseur;
use App\Models\Justificatif;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DepenseService
{
    public function create(array $data, User $user, array $files = []): Depense
    {
        Gate::forUser($user)->authorize('create', Depense::class);
        $data['user_id'] = $user->id;
        $data['circuit_validation_directe'] = $user->isDirecteur();

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
                unset($data['lignes'], $data['justificatif'], $data['justificatifs'], $data['prix_total'], $data['statut_validation'], $data['valide_par'], $data['valide_le'], $data['motif_rejet'], $data['version_courante'], $data['numero_facture_normalise']);
                if ($depense) {
                    unset($data['user_id'], $data['circuit_validation_directe']);
                }
                $chantierId = $data['chantier_id'] ?? $depense?->chantier_id;
                Chantier::findOrFail($chantierId);
                abort_unless($user->hasAccessToChantier($chantierId), 403);
                $this->resolveFournisseur($data, $depense);
                if ($data['fournisseur_id'] ?? $depense?->fournisseur_id) {
                    Fournisseur::query()->whereKey($data['fournisseur_id'] ?? $depense?->fournisseur_id)->lockForUpdate()->firstOrFail();
                }
                if (array_key_exists('numero_facture', $data)) {
                    $data['numero_facture_normalise'] = $this->normaliserNumero($data['numero_facture']);
                }
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
                    $this->ensureInitialHistory($depense);
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
                $this->checkDuplicates($depense, $files, $user);
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

                if ($depense->wasRecentlyCreated) {
                    $this->recordHistory($depense, 'creation', $user);
                } else {
                    $depense->increment('version_courante');
                    $this->recordHistory($depense, $wasRejected ? 'resoumission' : 'modification', $user, $wasRejected ? 'rejetee' : 'en_attente');
                }

                return $depense->fresh(['lignes.categorieDepense', 'lignes.uniteMesure', 'justificatifs', 'categorie', 'unite']);
            });
        } catch (\Throwable $exception) {
            // Une transaction SQL ne peut pas annuler les fichiers déjà écrits.
            Storage::disk('private')->delete($paths);
            throw $exception;
        }
    }

    private function resolveFournisseur(array &$data, ?Depense $depense): void
    {
        if (! array_key_exists('fournisseur', $data) && ! array_key_exists('fournisseur_id', $data)) {
            return;
        }

        $nom = trim((string) ($data['fournisseur'] ?? (isset($data['fournisseur_id']) ? '' : ($depense?->fournisseur ?? ''))));
        $reference = isset($data['fournisseur_id'])
            ? Fournisseur::query()->where('is_active', true)->findOrFail($data['fournisseur_id'])
            : null;
        if ($reference && $nom !== '' && Fournisseur::normaliser($nom) !== $reference->nom_normalise) {
            throw ValidationException::withMessages(['fournisseur' => 'Le fournisseur choisi ne correspond pas au nom saisi.']);
        }
        if (! $reference && $nom !== '') {
            if ($depense?->fournisseur_id && Fournisseur::normaliser($nom) === Fournisseur::normaliser((string) $depense->fournisseur)) {
                $reference = $depense->fournisseurReference;
            }
        }
        if (! $reference && $nom !== '') {
            $normalise = Fournisseur::normaliser($nom);
            if ($normalise === '') {
                throw ValidationException::withMessages(['fournisseur' => 'Saisissez un nom de fournisseur valide.']);
            }
            $reference = Fournisseur::query()->createOrFirst(
                ['nom_normalise' => $normalise],
                ['nom' => Str::squish($nom), 'is_active' => true]
            );
            if (! $reference->is_active && $reference->id !== $depense?->fournisseur_id) {
                throw ValidationException::withMessages(['fournisseur' => 'Ce fournisseur est désactivé.']);
            }
        }
        $data['fournisseur_id'] = $reference?->id;
        $data['fournisseur'] = $reference?->nom;
    }

    private function normaliserNumero(?string $number): ?string
    {
        $normalise = preg_replace('/[^A-Z0-9]/', '', strtoupper(Str::ascii(trim((string) $number))));

        return $normalise !== '' ? $normalise : null;
    }

    private function checkDuplicates(Depense $depense, array $files, User $user): void
    {
        $sameInvoice = $depense->fournisseur_id && $depense->numero_facture_normalise
            ? Depense::query()->where('fournisseur_id', $depense->fournisseur_id)
                ->where('numero_facture_normalise', $depense->numero_facture_normalise)
                ->whereKeyNot($depense->id)->first()
            : null;
        if ($sameInvoice) {
            $reference = Gate::forUser($user)->allows('view', $sameInvoice) ? ' #'.$sameInvoice->id : '';
            throw ValidationException::withMessages([
                'numero_facture' => 'Doublon détecté : la facture'.$reference.' de ce fournisseur porte déjà ce numéro.',
            ]);
        }

        $pendingHashes = [];
        foreach ($files as $file) {
            $hash = hash_file('sha256', $file->getRealPath());
            if ($hash === false) {
                throw ValidationException::withMessages(['justificatif' => 'Impossible de vérifier le justificatif.']);
            }
            if (isset($pendingHashes[$hash]) || $depense->justificatifs()->where('sha256', $hash)->exists()) {
                throw ValidationException::withMessages(['justificatif' => 'Ce justificatif est déjà joint à la facture.']);
            }
            $pendingHashes[$hash] = true;
            DB::table('justificatif_empreintes')->insertOrIgnore(['sha256' => $hash]);
            DB::table('justificatif_empreintes')->where('sha256', $hash)->lockForUpdate()->first();
            $sameFile = Justificatif::query()->where('sha256', $hash)->whereHas('depense', fn ($query) => $query->whereKeyNot($depense->id))->first();
            if ($sameFile) {
                $reference = Gate::forUser($user)->allows('view', $sameFile->depense) ? ' #'.$sameFile->depense_id : '';
                throw ValidationException::withMessages([
                    'justificatif' => 'Doublon détecté : le justificatif est identique à celui de la facture'.$reference.'.',
                ]);
            }
        }
    }

    private function correctionState(Depense $depense): array
    {
        $fields = [
            'chantier_id', 'numero_facture', 'numero_facture_normalise', 'fournisseur', 'fournisseur_id', 'date_facture', 'id_produit',
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

    private function ensureInitialHistory(Depense $depense): void
    {
        if ($depense->historique()->exists()) {
            return;
        }

        $this->recordHistory($depense, 'etat_initial', null, $depense->statut_validation, $depense->motif_rejet);
    }

    private function recordHistory(Depense $depense, string $action, ?User $actor, ?string $previousStatus = null, ?string $reason = null): void
    {
        $current = $depense->fresh();
        $snapshot = $current->only([
            'chantier_id', 'user_id', 'circuit_validation_directe', 'numero_facture', 'numero_facture_normalise', 'fournisseur', 'fournisseur_id', 'date_facture',
            'id_produit', 'nom_produit', 'unite_id', 'quantite', 'prix_unitaire',
            'prix_total', 'categorie_id', 'observations', 'statut_paiement',
            'statut_livraison', 'statut_validation', 'valide_par', 'motif_rejet',
        ]);
        $snapshot['date_facture'] = $current->date_facture?->toDateString();
        $snapshot['valide_le'] = $current->valide_le?->toIso8601String();
        $snapshot['lignes'] = $current->lignes()->orderBy('id')->get()->map(fn ($line) => $line->only([
            'designation', 'categorie_depense_id', 'unite_mesure_id', 'quantite', 'prix_unitaire', 'montant', 'observations',
        ]))->all();
        $snapshot['justificatifs'] = $current->justificatifs()->orderBy('id')->get()->map(fn ($file) => $file->only([
            'id', 'nom_fichier', 'disk', 'chemin_fichier', 'type_fichier', 'taille_fichier', 'sha256',
        ]))->all();

        $current->historique()->create([
            'version' => $current->version_courante,
            'action' => $action,
            'statut_avant' => $previousStatus,
            'statut_apres' => $current->statut_validation,
            'acteur_id' => $actor?->id,
            'acteur_nom' => $actor?->name,
            'acteur_role' => $actor?->role,
            'motif' => $reason,
            'instantane' => $snapshot,
        ]);
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
                'sha256' => hash_file('sha256', $file->getRealPath()),
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
            $this->ensureInitialHistory($locked);
            $previousStatus = $locked->statut_validation;
            $locked->update($data);
            if ($data['statut_validation'] === 'validee') {
                $chantier->updateBudgetConsomme();
            }

            $this->recordHistory($locked, match ($data['statut_validation']) {
                'acceptee' => 'approbation',
                'validee' => 'validation',
                'rejetee' => 'rejet',
            }, $user, $previousStatus, $data['motif_rejet'] ?? null);

            return $locked->fresh(['validateur', 'categorie', 'unite']);
        });
    }

    public function delete(Depense $depense, User $user): bool
    {
        return DB::transaction(function () use ($depense, $user) {
            $locked = Depense::query()->lockForUpdate()->findOrFail($depense->id);
            Gate::forUser($user)->authorize('delete', $locked);

            $this->ensureInitialHistory($locked);
            $this->recordHistory($locked, 'suppression', $user, $locked->statut_validation);

            // Le soft delete conserve les pièces pour l'audit et une éventuelle restauration.
            return (bool) $locked->delete();
        });
    }
}
