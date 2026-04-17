<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lignes_depense', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depense_id')->constrained()->onDelete('cascade');
            $table->foreignId('categorie_depense_id')->nullable()->constrained('categories_depenses')->onDelete('set null');
            
            // Détails de l'article
            $table->string('designation'); // Ciment, Carreau, Sable, etc.
            $table->foreignId('unite_mesure_id')->nullable()->constrained('unites_mesure')->onDelete('set null');
            $table->decimal('quantite', 10, 2);
            $table->decimal('prix_unitaire', 15, 2);
            $table->decimal('montant', 15, 2); // quantite * prix_unitaire
            
            $table->text('observations')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lignes_depense');
    }
};
