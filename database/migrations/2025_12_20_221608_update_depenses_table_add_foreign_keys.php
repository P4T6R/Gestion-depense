<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            // Supprimer les anciennes colonnes string
            $table->dropColumn(['categorie', 'unite']);
            
            // Ajouter les clés étrangères
            $table->foreignId('categorie_id')->after('prix_total')->constrained('categories_depenses')->onDelete('restrict');
            $table->foreignId('unite_id')->after('nom_produit')->constrained('unites_mesure')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropForeign(['categorie_id']);
            $table->dropForeign(['unite_id']);
            $table->dropColumn(['categorie_id', 'unite_id']);
            
            // Remettre les anciennes colonnes
            $table->string('categorie')->after('prix_total');
            $table->string('unite')->after('nom_produit');
        });
    }
};