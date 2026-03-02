<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Шаг 1: Удалить дубли БЕЗ + если запись С + уже существует
        // (public_users имеет unique constraint на phone)
        DB::statement("
            DELETE FROM public_users
            WHERE phone NOT LIKE '+%'
              AND phone ~ '^[0-9]'
              AND EXISTS (
                  SELECT 1 FROM public_users pu2
                  WHERE pu2.phone = '+' || public_users.phone
              )
        ");

        // Аналогично для clients (может не иметь unique, но на всякий случай)
        DB::statement("
            DELETE FROM clients
            WHERE phone IS NOT NULL
              AND phone NOT LIKE '+%'
              AND phone ~ '^[0-9]'
              AND deleted_at IS NOT NULL
              AND EXISTS (
                  SELECT 1 FROM clients c2
                  WHERE c2.phone = '+' || clients.phone
                    AND c2.agency_id = clients.agency_id
                    AND c2.deleted_at IS NULL
              )
        ");

        // Шаг 2: Нормализовать оставшиеся номера без +
        $tables = ['public_users', 'clients'];

        foreach ($tables as $table) {
            DB::table($table)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->whereRaw("phone NOT LIKE '+%'")
                ->whereRaw("phone ~ '^[0-9]'")
                ->update(['phone' => DB::raw("'+' || phone")]);
        }
    }

    public function down(): void
    {
        // Необратимая нормализация
    }
};
