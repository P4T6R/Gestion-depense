<?php

namespace App\Console\Commands;

use App\Models\Justificatif;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class EmpreintesJustificatifs extends Command
{
    protected $signature = 'justificatifs:empreintes';

    protected $description = 'Calcule les empreintes SHA-256 manquantes des justificatifs déjà enregistrés';

    public function handle(): int
    {
        $updated = 0;
        $missing = 0;
        Justificatif::whereNull('sha256')->orderBy('id')->chunkById(100, function ($files) use (&$updated, &$missing) {
            foreach ($files as $file) {
                $disk = Storage::disk($file->disk ?: 'private');
                $stream = $disk->exists($file->chemin_fichier) ? $disk->readStream($file->chemin_fichier) : false;
                if ($stream === false) {
                    $missing++;
                    $this->warn('Fichier introuvable pour le justificatif #'.$file->id);

                    continue;
                }
                try {
                    $context = hash_init('sha256');
                    hash_update_stream($context, $stream);
                    $file->update(['sha256' => hash_final($context)]);
                    $updated++;
                } finally {
                    fclose($stream);
                }
            }
        });

        $this->info("$updated empreinte(s) calculée(s), $missing fichier(s) introuvable(s).");

        return $missing === 0 ? self::SUCCESS : self::FAILURE;
    }
}
