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
        Schema::create('savings_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('asset_id')->nullable()->constrained()->onDelete('set null');

            $table->string('name');
            $table->text('description')->nullable();
            $table->bigInteger('target_amount_cents');
            $table->bigInteger('current_amount_cents')->default(0);

            $table->date('start_date');
            $table->date('target_date')->nullable();

            $table->enum('status', ['active', 'completed', 'abandoned', 'paused'])->default('active');
            $table->integer('priority')->default(0);

            // Paramètres de notification
            $table->boolean('notify_milestones')->default(true);
            $table->boolean('notify_risk')->default(true);
            $table->boolean('notify_reminder')->default(true);
            $table->integer('reminder_day_of_month')->nullable();
            $table->bigInteger('suggested_monthly_amount_cents')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'target_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_goals');
    }
};
