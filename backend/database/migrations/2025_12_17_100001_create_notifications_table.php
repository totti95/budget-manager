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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'budget_exceeded', 'savings_goal_reached'
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable(); // Store budgetId, subcategoryId, percentage, amounts, etc.
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'read']); // For unread count queries
            $table->index(['user_id', 'created_at']); // For ordered listing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
