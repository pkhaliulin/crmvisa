<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite не поддерживает ALTER TABLE DROP CONSTRAINT
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // PostgreSQL: обновляем CHECK constraint, добавляя conditional_required
        DB::statement("ALTER TABLE country_visa_requirements DROP CONSTRAINT IF EXISTS country_visa_requirements_requirement_level_check");
        DB::statement("ALTER TABLE country_visa_requirements ADD CONSTRAINT country_visa_requirements_requirement_level_check CHECK (requirement_level IN ('required', 'conditional_required', 'recommended', 'confirmation_only'))");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE country_visa_requirements DROP CONSTRAINT IF EXISTS country_visa_requirements_requirement_level_check");
        DB::statement("ALTER TABLE country_visa_requirements ADD CONSTRAINT country_visa_requirements_requirement_level_check CHECK (requirement_level IN ('required', 'recommended', 'confirmation_only'))");
    }
};
