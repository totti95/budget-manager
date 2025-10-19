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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('label')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insérer les rôles par défaut
        DB::table('roles')->insert([
            ['label' => 'user', 'description' => 'Utilisateur standard', 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'admin', 'description' => 'Administrateur', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
