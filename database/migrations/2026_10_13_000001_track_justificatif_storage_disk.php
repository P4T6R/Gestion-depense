<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('justificatifs', function (Blueprint $table) {
            // Les anciennes pièces restent identifiables pour la migration vers le stockage privé.
            $table->string('disk', 20)->default('public');
        });
    }

    public function down(): void
    {
        if (DB::table('justificatifs')->where('disk', 'private')->exists()) {
            throw new RuntimeException('Ne pas supprimer les métadonnées du stockage privé tant que des pièces y sont conservées.');
        }
        Schema::table('justificatifs', fn (Blueprint $table) => $table->dropColumn('disk'));
    }
};
