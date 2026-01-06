<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained('roles')->onDelete('restrict');
            $table->softDeletes();
        });

        // Définir le rôle par défaut 'user' pour tous les utilisateurs existants
        $userRoleId = DB::table('roles')->where('label', 'user')->value('id');
        DB::table('users')->whereNull('role_id')->update(['role_id' => $userRoleId]);

        // Rendre la colonne role_id obligatoire après avoir défini les valeurs
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropColumn('role_id');
            $table->dropSoftDeletes();
        });
    }
};
