<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // activity_log использует bigint для subject_id/causer_id,
        // но все модели проекта используют UUID (string).
        // Меняем на varchar(36) для совместимости.
        DB::statement('ALTER TABLE activity_log ALTER COLUMN subject_id TYPE varchar(36)');
        DB::statement('ALTER TABLE activity_log ALTER COLUMN causer_id TYPE varchar(36)');
    }

    public function down(): void
    {
        // Обратная миграция невозможна без потери данных
        // (UUID нельзя привести к bigint)
    }
};
