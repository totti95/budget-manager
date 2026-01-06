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
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->boolean('savings_goal_milestone_enabled')->default(true);
            $table->boolean('savings_goal_risk_enabled')->default(true);
            $table->boolean('savings_goal_reminder_enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notification_settings', function (Blueprint $table) {
            $table->dropColumn([
                'savings_goal_milestone_enabled',
                'savings_goal_risk_enabled',
                'savings_goal_reminder_enabled',
            ]);
        });
    }
};
