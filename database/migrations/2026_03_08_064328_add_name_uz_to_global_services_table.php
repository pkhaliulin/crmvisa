<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('global_services', function (Blueprint $table) {
            $table->string('name_uz')->nullable()->after('name');
        });

        $translations = [
            'Первичная консультация' => 'Boshlang\'ich konsultatsiya',
            'Подготовка документов' => 'Hujjatlarni tayyorlash',
            'Заполнение анкеты' => 'Anketani to\'ldirish',
            'Запись в посольство' => 'Elchixonaga yozilish',
            'Перевод документов' => 'Hujjatlarni tarjima qilish',
            'Нотариальное заверение' => 'Notarial tasdiqlash',
            'Фотоуслуги' => 'Foto xizmatlar',
            'Страховка' => 'Sug\'urta',
            'Бронирование отеля' => 'Mehmonxona bron qilish',
            'Бронирование авиабилетов' => 'Aviabilet bron qilish',
            'Сопровождение в посольство' => 'Elchixonaga hamrohlik',
            'Апостиль' => 'Apostil',
            'Легализация' => 'Legalizatsiya',
            'Справка о несудимости' => 'Sudlanmaganlik ma\'lumotnomasi',
            'Медицинская справка' => 'Tibbiy ma\'lumotnoma',
            'Финансовые гарантии' => 'Moliyaviy kafolatlar',
            'Сопроводительное письмо' => 'Hamroh xat',
            'Мотивационное письмо' => 'Motivatsion xat',
            'Пригласительное письмо' => 'Taklifnoma',
            'Бизнес-план' => 'Biznes-reja',
            'Курьерская доставка' => 'Kuryerlik yetkazib berish',
            'VIP-обслуживание' => 'VIP xizmat',
            'Онлайн-консультация (видеозвонок)' => 'Onlayn konsultatsiya (videoqo\'ng\'iroq)',
            'Сопровождение сбора документов' => 'Hujjatlar yig\'ishga hamrohlik',
            'Проверка комплектности документов' => 'Hujjatlar to\'liqligini tekshirish',
            'Приоритетная обработка' => 'Ustuvor ishlov berish',
        ];

        foreach ($translations as $nameRu => $nameUz) {
            DB::table('global_services')
                ->where('name', $nameRu)
                ->update(['name_uz' => $nameUz]);
        }
    }

    public function down(): void
    {
        Schema::table('global_services', function (Blueprint $table) {
            $table->dropColumn('name_uz');
        });
    }
};
