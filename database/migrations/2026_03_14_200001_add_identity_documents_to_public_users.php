<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('public_users', function (Blueprint $table) {
            // Раздельные поля имени (латиница из загранпаспорта, кириллица из ID)
            $table->text('first_name_lat')->nullable()->after('name');
            $table->text('last_name_lat')->nullable()->after('first_name_lat');
            $table->text('middle_name_lat')->nullable()->after('last_name_lat');
            $table->text('first_name_cyr')->nullable()->after('middle_name_lat');
            $table->text('last_name_cyr')->nullable()->after('first_name_cyr');
            $table->text('middle_name_cyr')->nullable()->after('last_name_cyr');

            // ПИНФЛ / PNFL
            $table->text('pnfl')->nullable()->after('middle_name_cyr');

            // Место рождения
            $table->text('place_of_birth')->nullable()->after('gender');

            // ID-документ (внутренний паспорт / ID-карта)
            $table->string('id_doc_type', 20)->nullable()->after('passport_expires_at');
            $table->text('id_doc_number')->nullable()->after('id_doc_type');
            $table->text('id_doc_expires_at')->nullable()->after('id_doc_number');
            $table->text('id_doc_issue_date')->nullable()->after('id_doc_expires_at');
            $table->text('id_doc_issued_by')->nullable()->after('id_doc_issue_date');
            $table->string('id_doc_ocr_status', 20)->nullable()->after('id_doc_issued_by');
            $table->text('id_doc_ocr_data')->nullable()->after('id_doc_ocr_status');
            $table->text('id_doc_file_path')->nullable()->after('id_doc_ocr_data');

            // Загранпаспорт — дополнительные поля (passport_number, passport_expires_at уже есть)
            $table->text('passport_issue_date')->nullable()->after('id_doc_file_path');
            $table->text('passport_issued_by')->nullable()->after('passport_issue_date');
            $table->text('passport_country')->nullable()->after('passport_issued_by');
            $table->text('passport_file_path')->nullable()->after('passport_country');

            // Переименование OCR полей -> паспорт-специфичные (сохраняем старые для совместимости)
            $table->string('passport_ocr_status', 20)->nullable()->after('passport_file_path');
            $table->text('passport_ocr_data')->nullable()->after('passport_ocr_status');
        });

        // Перенести данные из старых полей в новые
        \DB::statement("UPDATE public_users SET passport_ocr_status = ocr_status WHERE ocr_status IS NOT NULL");
        \DB::statement("UPDATE public_users SET passport_ocr_data = ocr_raw_data WHERE ocr_raw_data IS NOT NULL");
    }

    public function down(): void
    {
        Schema::table('public_users', function (Blueprint $table) {
            $table->dropColumn([
                'first_name_lat', 'last_name_lat', 'middle_name_lat',
                'first_name_cyr', 'last_name_cyr', 'middle_name_cyr',
                'pnfl', 'place_of_birth',
                'id_doc_type', 'id_doc_number', 'id_doc_expires_at',
                'id_doc_issue_date', 'id_doc_issued_by',
                'id_doc_ocr_status', 'id_doc_ocr_data', 'id_doc_file_path',
                'passport_issue_date', 'passport_issued_by', 'passport_country',
                'passport_file_path', 'passport_ocr_status', 'passport_ocr_data',
            ]);
        });
    }
};
