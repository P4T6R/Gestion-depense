<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Les champs mono-produit ne s'appliquent pas aux factures multi-lignes.
        DB::statement('ALTER TABLE depenses
            MODIFY nom_produit VARCHAR(255) NULL,
            MODIFY unite_id BIGINT UNSIGNED NULL,
            MODIFY categorie_id BIGINT UNSIGNED NULL,
            MODIFY quantite DECIMAL(10, 2) NULL,
            MODIFY prix_unitaire DECIMAL(15, 2) NULL');
    }

    public function down(): void
    {
        if (DB::table('depenses')->whereNull('nom_produit')->orWhereNull('unite_id')
            ->orWhereNull('categorie_id')->orWhereNull('quantite')->orWhereNull('prix_unitaire')->exists()) {
            throw new RuntimeException('Convertir les factures multi-lignes avant de restaurer le schéma mono-produit.');
        }
        DB::statement('ALTER TABLE depenses
            MODIFY nom_produit VARCHAR(255) NOT NULL,
            MODIFY unite_id BIGINT UNSIGNED NOT NULL,
            MODIFY categorie_id BIGINT UNSIGNED NOT NULL,
            MODIFY quantite DECIMAL(10, 2) NOT NULL,
            MODIFY prix_unitaire DECIMAL(15, 2) NOT NULL');
    }
};
