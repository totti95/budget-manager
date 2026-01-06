<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('budget_templates', function (Blueprint $table) {
            $table->bigInteger('revenue_cents')->nullable()->after('is_default');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('budget_templates', function (Blueprint $table) {
            $table->dropColumn('revenue_cents');
        });
    }
};
