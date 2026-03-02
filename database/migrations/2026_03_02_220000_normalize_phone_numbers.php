<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Нормализация телефонов: добавляем + к номерам без него
        $tables = ['public_users', 'clients'];

        foreach ($tables as $table) {
            DB::table($table)
                ->whereNotNull('phone')
                ->where('phone', '!=', '')
                ->whereRaw("phone NOT LIKE '+%'")
                ->whereRaw("phone ~ '^[0-9]'")
                ->update(['phone' => DB::raw("'+' || phone")]);
        }

        // Удаляем дубли в public_users (без +), оставляя запись с + если есть обе
        $duplicates = DB::select("
            SELECT pu1.id as dup_id
            FROM public_users pu1
            JOIN public_users pu2 ON pu2.phone = '+' || pu1.phone
            WHERE pu1.phone NOT LIKE '+%'
        ");

        foreach ($duplicates as $dup) {
            DB::table('public_users')->where('id', $dup->dup_id)->delete();
        }
    }

    public function down(): void
    {
        // Необратимая нормализация
    }
};
