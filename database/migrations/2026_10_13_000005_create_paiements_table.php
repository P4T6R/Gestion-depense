<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depense_id')->constrained('depenses')->restrictOnDelete();
            $table->decimal('montant', 15, 2);
            $table->date('date_paiement');
            $table->string('mode', 32);
            $table->string('reference');
            $table->foreignId('enregistre_par')->nullable()->constrained('users')->nullOnDelete();
            $table->string('enregistre_par_nom');
            $table->boolean('reprise_historique')->default(false);
            $table->timestamp('annule_le')->nullable();
            $table->foreignId('annule_par')->nullable()->constrained('users')->nullOnDelete();
            $table->string('annule_par_nom')->nullable();
            $table->text('motif_annulation')->nullable();
            $table->timestamps();
            $table->index(['depense_id', 'annule_le']);
            $table->index(['mode', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
