<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Конвертация documents.extracted_data в TEXT для поддержки encrypted casts.
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE documents ALTER COLUMN extracted_data TYPE text USING extracted_data::text');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE documents ALTER COLUMN extracted_data TYPE jsonb USING extracted_data::jsonb');
        }
    }
};
