<?php

namespace App\Http\Controllers;

use App\Models\Justificatif;
use Illuminate\Support\Facades\Storage;

class JustificatifController extends Controller
{
    public function __invoke(Justificatif $justificatif)
    {
        $depense = $justificatif->depense()->firstOrFail();
        $this->authorize('view', $depense);
        abort_unless(in_array($justificatif->disk, ['public', 'private'], true), 404);
        $disk = Storage::disk($justificatif->disk);
        abort_unless($disk->exists($justificatif->chemin_fichier), 404);

        return $disk->response($justificatif->chemin_fichier, $justificatif->nom_fichier, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
