<?php

namespace App\Console\Commands;

use App\Models\Justificatif;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ProtectJustificatifs extends Command
{
    protected $signature = 'justificatifs:protect {--dry-run : Inspecter sans modifier les fichiers}';

    protected $description = 'Copier et vérifier les pièces publiques en stockage privé avant de retirer les copies publiques';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('private');
        $failures = 0;
        $migrated = 0;
        Justificatif::query()->chunkById(100, function ($items) use ($public, $private, &$failures, &$migrated) {
            foreach ($items as $item) {
                $path = $item->chemin_fichier;
                if ($item->disk === 'private' && ! $public->exists($path)) {
                    continue;
                }
                if (! in_array($item->disk, ['public', 'private'], true)) {
                    $this->error("Pièce {$item->id} : disque non pris en charge.");
                    $failures++;

                    continue;
                }
                if ($this->option('dry-run')) {
                    $this->line("Pièce {$item->id} : migration ou nettoyage à effectuer.");

                    continue;
                }
                try {
                    if ($public->exists($path)) {
                        if ($item->disk === 'public' && ! $private->exists($path)) {
                            $stream = $public->readStream($path);
                            if ($stream === false) {
                                throw new \RuntimeException('Source illisible.');
                            }
                            try {
                                if (! $private->put($path, $stream)) {
                                    throw new \RuntimeException('Copie impossible.');
                                }
                            } finally {
                                fclose($stream);
                            }
                        }
                        // Ne jamais retirer l'original sans vérifier les octets copiés.
                        if (! $private->exists($path) || hash_file('sha256', $public->path($path)) !== hash_file('sha256', $private->path($path))) {
                            throw new \RuntimeException('Vérification de la copie échouée.');
                        }
                    } elseif (! $private->exists($path) || ! Justificatif::where('disk', 'private')->where('chemin_fichier', $path)->exists()) {
                        throw new \RuntimeException('Fichier source manquant.');
                    }
                    $item->update(['disk' => 'private']);
                    if ($public->exists($path) && ! $public->delete($path)) {
                        throw new \RuntimeException('Retrait de la copie publique impossible ; relancer la commande.');
                    }
                    $migrated++;
                } catch (\Throwable $exception) {
                    $this->error("Pièce {$item->id} : {$exception->getMessage()}");
                    $failures++;
                }
            }
        });
        $this->info("Pièces protégées : {$migrated}. Échecs : {$failures}.");

        return $failures > 0 ? self::FAILURE : self::SUCCESS;
    }
}
