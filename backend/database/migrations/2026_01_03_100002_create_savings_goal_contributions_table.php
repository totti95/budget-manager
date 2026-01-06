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
        Schema::create('savings_goal_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('savings_goal_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->bigInteger('amount_cents');
            $table->date('contribution_date');
            $table->string('note')->nullable();

            $table->timestamps();

            $table->index(['savings_goal_id', 'contribution_date'], 'sg_contrib_goal_date_idx');
            $table->index(['user_id', 'contribution_date'], 'sg_contrib_user_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('savings_goal_contributions');
    }
};
