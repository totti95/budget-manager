<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('template_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_template_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->bigInteger('planned_amount_cents')->default(0);
            $table->timestamps();

            $table->index(['budget_template_id', 'sort_order']);
        });

        Schema::create('template_subcategories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->bigInteger('planned_amount_cents')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['template_category_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_subcategories');
        Schema::dropIfExists('template_categories');
    }
};
