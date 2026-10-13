<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('depenses', function (Blueprint $table) {
            $table->unsignedInteger('version_courante')->default(1);
        });

        Schema::create('depense_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depense_id')->constrained('depenses')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('action', 32);
            $table->string('statut_avant', 20)->nullable();
            $table->string('statut_apres', 20);
            $table->foreignId('acteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acteur_nom')->nullable();
            $table->string('acteur_role', 32)->nullable();
            $table->text('motif')->nullable();
            $table->json('instantane');
            $table->timestamp('created_at', 6)->useCurrent();
            $table->index(['depense_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depense_historiques');
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropColumn('version_courante');
        });
    }
};
