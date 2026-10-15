<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            // Index sur user_id pour filtrer par créateur
            $table->index('user_id', 'idx_depenses_user');

            // Index sur chantier_id pour filtrer par chantier
            $table->index('chantier_id', 'idx_depenses_chantier');

            // Index sur statut_validation pour filtrer par statut
            $table->index('statut_validation', 'idx_depenses_statut');

            // Index sur categorie_id pour filtrer par catégorie
            $table->index('categorie_id', 'idx_depenses_categorie');

            // Index composé chantier + statut (requêtes fréquentes)
            $table->index(['chantier_id', 'statut_validation'], 'idx_depenses_chantier_statut');

            // Index composé user + statut (mes dépenses en attente)
            $table->index(['user_id', 'statut_validation'], 'idx_depenses_user_statut');

            // Index sur created_at pour le tri par date
            $table->index('created_at', 'idx_depenses_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropIndex('idx_depenses_user');
            $table->dropIndex('idx_depenses_chantier');
            $table->dropIndex('idx_depenses_statut');
            $table->dropIndex('idx_depenses_categorie');
            $table->dropIndex('idx_depenses_chantier_statut');
            $table->dropIndex('idx_depenses_user_statut');
            $table->dropIndex('idx_depenses_created');
        });
    }
};
