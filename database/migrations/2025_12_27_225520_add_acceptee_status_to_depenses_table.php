<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modifier l'ENUM pour ajouter 'acceptee' entre 'en_attente' et 'validee'
        DB::statement("ALTER TABLE `depenses` MODIFY `statut_validation` ENUM('en_attente', 'acceptee', 'validee', 'rejetee') NOT NULL DEFAULT 'en_attente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Retour à l'ENUM original
        DB::statement("ALTER TABLE `depenses` MODIFY `statut_validation` ENUM('en_attente', 'validee', 'rejetee') NOT NULL DEFAULT 'en_attente'");
    }
};
