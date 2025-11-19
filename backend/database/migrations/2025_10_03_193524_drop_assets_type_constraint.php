<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the check constraint created by the enum
        // MySQL and PostgreSQL have different syntax
        if (\DB::getDriverName() === 'pgsql') {
            \DB::statement('ALTER TABLE assets DROP CONSTRAINT IF EXISTS assets_type_check');
        } elseif (\DB::getDriverName() === 'mysql') {
            // MySQL doesn't create automatic check constraints for enums, so nothing to drop
            // But if it exists, we need to check first
            $constraintExists = \DB::select("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'assets' AND CONSTRAINT_NAME = 'assets_type_check'");
            if (! empty($constraintExists)) {
                \DB::statement('ALTER TABLE assets DROP CHECK assets_type_check');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-add the enum constraint if needed
        if (\DB::getDriverName() === 'pgsql') {
            \DB::statement("ALTER TABLE assets ADD CONSTRAINT assets_type_check CHECK (type IN ('immobilier', 'épargne', 'investissement', 'autre'))");
        } elseif (\DB::getDriverName() === 'mysql') {
            \DB::statement("ALTER TABLE assets ADD CONSTRAINT assets_type_check CHECK (type IN ('immobilier', 'épargne', 'investissement', 'autre'))");
        }
    }
};
