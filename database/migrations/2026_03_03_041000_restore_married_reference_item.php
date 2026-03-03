<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Восстанавливаем удалённую запись married в справочнике marital_status
        $exists = DB::table('reference_items')
            ->where('category', 'marital_status')
            ->where('code', 'married')
            ->exists();

        if (! $exists) {
            DB::table('reference_items')->insert([
                'id'         => Str::uuid()->toString(),
                'category'   => 'marital_status',
                'code'       => 'married',
                'label_ru'   => 'Женат/Замужем',
                'label_uz'   => 'Turmush qurgan',
                'sort_order' => 0,
                'is_active'  => true,
                'metadata'   => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Не удаляем — это восстановление данных
    }
};
