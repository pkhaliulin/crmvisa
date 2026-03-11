<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Конвертация PII-колонок в TEXT для поддержки encrypted casts.
     * Зашифрованные данные длиннее оригинальных (base64 + IV + tag).
     *
     * Затронутые таблицы:
     * - public_users: recovery_email
     * - public_user_family_members: passport_number, passport_expires_at, dob
     * - marketplace_leads: client_name, client_phone, client_email
     * - client_profiles: employer_name
     * - case_group_members: name
     */
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // public_users — recovery_email
        DB::statement('ALTER TABLE public_users ALTER COLUMN recovery_email TYPE text');

        // public_user_family_members — passport_number, passport_expires_at, dob
        DB::statement('ALTER TABLE public_user_family_members ALTER COLUMN passport_number TYPE text');
        DB::statement('ALTER TABLE public_user_family_members ALTER COLUMN passport_expires_at TYPE text USING passport_expires_at::text');
        DB::statement('ALTER TABLE public_user_family_members ALTER COLUMN dob TYPE text USING dob::text');

        // marketplace_leads — client_name, client_phone, client_email
        DB::statement('ALTER TABLE marketplace_leads ALTER COLUMN client_name TYPE text');
        DB::statement('ALTER TABLE marketplace_leads ALTER COLUMN client_phone TYPE text');
        DB::statement('ALTER TABLE marketplace_leads ALTER COLUMN client_email TYPE text');

        // client_profiles — employer_name
        DB::statement('ALTER TABLE client_profiles ALTER COLUMN employer_name TYPE text');

        // case_group_members — name
        DB::statement('ALTER TABLE case_group_members ALTER COLUMN name TYPE text');
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE public_users ALTER COLUMN recovery_email TYPE varchar(255)');

        DB::statement('ALTER TABLE public_user_family_members ALTER COLUMN passport_number TYPE varchar(20)');
        DB::statement('ALTER TABLE public_user_family_members ALTER COLUMN passport_expires_at TYPE date USING passport_expires_at::date');
        DB::statement('ALTER TABLE public_user_family_members ALTER COLUMN dob TYPE date USING dob::date');

        DB::statement('ALTER TABLE marketplace_leads ALTER COLUMN client_name TYPE varchar(255)');
        DB::statement('ALTER TABLE marketplace_leads ALTER COLUMN client_phone TYPE varchar(255)');
        DB::statement('ALTER TABLE marketplace_leads ALTER COLUMN client_email TYPE varchar(255)');

        DB::statement('ALTER TABLE client_profiles ALTER COLUMN employer_name TYPE varchar(255)');

        DB::statement('ALTER TABLE case_group_members ALTER COLUMN name TYPE varchar(255)');
    }
};
