<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('budget_subcategories', function (Blueprint $table) {
            $table->integer('default_spent_cents')->default(0)->after('allocated_cents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_subcategories', function (Blueprint $table) {
            $table->dropColumn('default_spent_cents');
        });
    }
};
