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
        Schema::create('wealth_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('recorded_at');
            $table->bigInteger('total_assets_cents')->default(0);
            $table->bigInteger('total_liabilities_cents')->default(0);
            $table->bigInteger('net_worth_cents')->default(0);
            $table->timestamps();

            // Ensure one entry per user per date
            $table->unique(['user_id', 'recorded_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wealth_history');
    }
};
