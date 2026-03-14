<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chantiers', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('lieu')->nullable();
            $table->text('description')->nullable();
            $table->decimal('budget_alloue', 15, 2); // Budget en FCFA
            $table->decimal('budget_consomme', 15, 2)->default(0);
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->enum('statut', ['actif', 'en_pause', 'termine', 'archive'])->default('actif');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chantiers');
    }
};