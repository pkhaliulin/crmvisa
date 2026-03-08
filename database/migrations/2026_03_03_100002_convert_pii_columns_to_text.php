<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Конвертация PII-колонок в TEXT для поддержки encrypted casts.
     * Зашифрованные данные длиннее оригинальных (base64 + IV + tag).
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            // clients — passport_number, phone, date_of_birth, passport_expires_at
            DB::statement('ALTER TABLE clients ALTER COLUMN passport_number TYPE text');
            DB::statement('ALTER TABLE clients ALTER COLUMN phone TYPE text');
            DB::statement('ALTER TABLE clients ALTER COLUMN date_of_birth TYPE text USING date_of_birth::text');
            DB::statement('ALTER TABLE clients ALTER COLUMN passport_expires_at TYPE text USING passport_expires_at::text');

            // users — phone
            DB::statement('ALTER TABLE users ALTER COLUMN phone TYPE text');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE clients ALTER COLUMN passport_number TYPE varchar(255)');
            DB::statement('ALTER TABLE clients ALTER COLUMN phone TYPE varchar(255)');
            DB::statement('ALTER TABLE clients ALTER COLUMN date_of_birth TYPE date USING date_of_birth::date');
            DB::statement('ALTER TABLE clients ALTER COLUMN passport_expires_at TYPE date USING passport_expires_at::date');
            DB::statement('ALTER TABLE users ALTER COLUMN phone TYPE varchar(255)');
        }
    }
};
