<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Конвертация PII-колонок public_users в TEXT для поддержки encrypted casts.
     * Зашифрованные данные длиннее оригинальных (base64 + IV + tag).
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE public_users ALTER COLUMN passport_number TYPE text');
            DB::statement('ALTER TABLE public_users ALTER COLUMN passport_expires_at TYPE text USING passport_expires_at::text');
            DB::statement('ALTER TABLE public_users ALTER COLUMN dob TYPE text USING dob::text');
            DB::statement('ALTER TABLE public_users ALTER COLUMN ocr_raw_data TYPE text USING ocr_raw_data::text');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE public_users ALTER COLUMN passport_number TYPE varchar(255)');
            DB::statement('ALTER TABLE public_users ALTER COLUMN passport_expires_at TYPE date USING passport_expires_at::date');
            DB::statement('ALTER TABLE public_users ALTER COLUMN dob TYPE date USING dob::date');
            DB::statement('ALTER TABLE public_users ALTER COLUMN ocr_raw_data TYPE jsonb USING ocr_raw_data::jsonb');
        }
    }
};
