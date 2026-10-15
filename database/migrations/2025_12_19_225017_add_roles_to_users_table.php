<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['administrateur', 'chef_chantier', 'comptable', 'directeur'])
                ->default('chef_chantier')
                ->after('email');
            $table->boolean('is_active')->default(true)->after('role');
            $table->string('phone')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'is_active', 'phone']);
        });
    }
};
