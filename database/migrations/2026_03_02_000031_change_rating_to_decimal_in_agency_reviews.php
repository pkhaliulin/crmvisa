<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE agency_reviews ALTER COLUMN rating TYPE DECIMAL(3,1)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE agency_reviews ALTER COLUMN rating TYPE SMALLINT USING rating::SMALLINT');
    }
};
