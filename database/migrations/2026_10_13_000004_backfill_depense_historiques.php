<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('depenses')->orderBy('id')->chunkById(100, function ($depenses) {
            foreach ($depenses as $depense) {
                $snapshot = (array) $depense;
                unset($snapshot['deleted_at'], $snapshot['created_at'], $snapshot['updated_at'], $snapshot['version_courante']);
                $snapshot['lignes'] = DB::table('lignes_depense')->where('depense_id', $depense->id)->orderBy('id')
                    ->get(['designation', 'categorie_depense_id', 'unite_mesure_id', 'quantite', 'prix_unitaire', 'montant', 'observations'])->map(fn ($line) => (array) $line)->all();
                $snapshot['justificatifs'] = DB::table('justificatifs')->where('depense_id', $depense->id)->orderBy('id')
                    ->get(['id', 'nom_fichier', 'disk', 'chemin_fichier', 'type_fichier', 'taille_fichier', 'sha256'])->map(fn ($file) => (array) $file)->all();
                DB::table('depense_historiques')->insert([
                    'depense_id' => $depense->id,
                    'version' => $depense->version_courante,
                    'action' => 'etat_initial',
                    'statut_avant' => $depense->statut_validation,
                    'statut_apres' => $depense->statut_validation,
                    'motif' => $depense->motif_rejet,
                    'instantane' => json_encode($snapshot, JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('depense_historiques')->where('action', 'etat_initial')->whereNull('acteur_id')->delete();
    }
};
