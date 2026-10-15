<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chantier_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Chef qui a saisi

            // Informations produit
            $table->string('id_produit')->nullable();
            $table->string('nom_produit');
            $table->string('unite'); // sac, tonne, m3, etc.
            $table->decimal('quantite', 10, 2);
            $table->decimal('prix_unitaire', 15, 2);
            $table->decimal('prix_total', 15, 2);

            // Catégorie et observations
            $table->string('categorie'); // Matériaux, Main d'œuvre, etc.
            $table->text('observations')->nullable();

            // Statuts
            $table->enum('statut_paiement', ['paye', 'non_paye'])->default('non_paye');
            $table->enum('statut_livraison', ['livre', 'non_livre'])->default('non_livre');
            $table->enum('statut_validation', ['en_attente', 'validee', 'rejetee'])->default('en_attente');

            // Validation comptable
            $table->foreignId('valide_par')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('valide_le')->nullable();
            $table->text('motif_rejet')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depenses');
    }
};
