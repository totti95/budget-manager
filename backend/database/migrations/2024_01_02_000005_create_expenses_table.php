<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->onDelete('cascade');
            $table->foreignId('budget_subcategory_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('label');
            $table->bigInteger('amount_cents');
            $table->string('payment_method')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['budget_id', 'date']);
            $table->index('budget_subcategory_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
