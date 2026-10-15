<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            // Infos facture
            $table->string('numero_facture')->nullable()->after('user_id');
            $table->string('fournisseur')->nullable()->after('numero_facture');
            $table->date('date_facture')->nullable()->after('fournisseur');

            // Le prix_total reste pour le montant total de la facture
            // On va recalculer automatiquement depuis les lignes
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropColumn(['numero_facture', 'fournisseur', 'date_facture']);
        });
    }
};
