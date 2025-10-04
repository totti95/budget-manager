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
        // Drop the check constraint created by the enum
        \DB::statement('ALTER TABLE assets DROP CONSTRAINT IF EXISTS assets_type_check');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the enum constraint if needed
        \DB::statement("ALTER TABLE assets ADD CONSTRAINT assets_type_check CHECK (type IN ('immobilier', 'épargne', 'investissement', 'autre'))");
    }
};
