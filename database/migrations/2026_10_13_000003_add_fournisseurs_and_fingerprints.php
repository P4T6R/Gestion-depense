<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fournisseurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('nom_normalise')->unique();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->text('adresse')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('depenses', function (Blueprint $table) {
            $table->boolean('circuit_validation_directe')->default(false)->after('user_id');
            $table->foreignId('fournisseur_id')->nullable()->after('fournisseur')->constrained('fournisseurs')->restrictOnDelete();
            $table->string('numero_facture_normalise')->nullable()->after('numero_facture');
            $table->index(['fournisseur_id', 'numero_facture_normalise'], 'idx_depenses_fournisseur_numero');
        });

        Schema::table('justificatifs', function (Blueprint $table) {
            $table->char('sha256', 64)->nullable()->index();
        });
        Schema::create('justificatif_empreintes', function (Blueprint $table) {
            $table->char('sha256', 64)->primary();
        });

        DB::table('depenses')->select('id', 'fournisseur', 'numero_facture')->orderBy('id')
            ->chunkById(500, function ($depenses) {
                foreach ($depenses as $depense) {
                    $values = [];
                    if (filled($depense->numero_facture)) {
                        $values['numero_facture_normalise'] = preg_replace('/[^A-Z0-9]/', '', strtoupper(Str::ascii($depense->numero_facture))) ?: null;
                    }
                    if (filled($depense->fournisseur)) {
                        $nom = Str::squish($depense->fournisseur);
                        $normalise = preg_replace('/[^a-z0-9]/', '', strtolower(Str::ascii($nom)));
                        if ($normalise !== '') {
                            DB::table('fournisseurs')->insertOrIgnore([
                                'nom' => $nom,
                                'nom_normalise' => $normalise,
                                'is_active' => true,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            $values['fournisseur_id'] = DB::table('fournisseurs')->where('nom_normalise', $normalise)->value('id');
                        }
                    }
                    if ($values !== []) {
                        DB::table('depenses')->where('id', $depense->id)->update($values);
                    }
                }
            });
        DB::table('depenses')->whereIn('user_id', DB::table('users')->where('role', 'directeur')->select('id'))
            ->update(['circuit_validation_directe' => true]);
    }

    public function down(): void
    {
        Schema::dropIfExists('justificatif_empreintes');
        Schema::table('justificatifs', function (Blueprint $table) {
            $table->dropColumn('sha256');
        });
        Schema::table('depenses', function (Blueprint $table) {
            $table->dropIndex('idx_depenses_fournisseur_numero');
            $table->dropConstrainedForeignId('fournisseur_id');
            $table->dropColumn('numero_facture_normalise');
            $table->dropColumn('circuit_validation_directe');
        });
        Schema::dropIfExists('fournisseurs');
    }
};
