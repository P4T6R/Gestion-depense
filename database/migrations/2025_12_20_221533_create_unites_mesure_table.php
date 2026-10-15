<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unites_mesure', function (Blueprint $table) {
            $table->id();
            $table->string('nom')->unique(); // Ex: Sac
            $table->string('symbole')->unique(); // Ex: sac
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unites_mesure');
    }
};
