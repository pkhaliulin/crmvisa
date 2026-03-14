<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            $table->string('name_uz')->nullable()->after('name');
            $table->text('description_uz')->nullable()->after('description');
        });

        // Заполнить узбекские переводы для основных документов
        $translations = [
            'foreign_passport' => ['Xorijiy pasport', 'Amal qilish muddati chiqish sanasidan kamida 3 oy. 2+ bo\'sh sahifa.'],
            'internal_passport' => ['Ichki pasport', 'Barcha to\'ldirilgan sahifalar skani: propiska, oilaviy ahvol, bolalar.'],
            'photo_3x4' => ['Foto 3.5x4.5 (2 dona)', 'Oq fon, ko\'zoynak va bosh kiyimsiz, 6 oydan eski emas.'],
            'application_form' => ['Anketa-ariza', 'Elchixona shakli bo\'yicha to\'ldiring va imzolang.'],
            'travel_insurance' => ['Tibbiy sug\'urta', 'Qoplamasi 30 000 EUR, Shengen zonasi, butun sayohat davri uchun.'],
            'hotel_booking' => ['Mehmonxona bron', 'Butun yashash davri uchun bronlash tasdig\'i.'],
            'air_tickets' => ['Aviabiletlar (borib-qaytish)', 'Aviabiletlar broni. Translit ruxsat etiladi.'],
            'bank_statement' => ['Bank kartasi bo\'yicha ko\'chirma (3-6 oy)', 'Oxirgi 3-6 oy uchun bank kartasi bo\'yicha tranzaksiyalar.'],
            'bank_balance_certificate' => ['Hisob qoldig\'i haqida ma\'lumotnoma', 'Joriy qoldiq bilan rasmiy ma\'lumotnoma. Bank muhri. 1 oydan eski emas.'],
            'employment_certificate' => ['Ish joyidan ma\'lumotnoma', 'Tashkilot blankida: lavozim, staj, maosh. 1 oydan eski emas.'],
            'income_certificate' => ['Daromad haqida ma\'lumotnoma', 'Rasmiy daromad tasdig\'i.'],
            'marriage_certificate' => ['Nikoh guvohnomasi', 'Nikohda bo\'lsa — majburiy.'],
            'child_birth_certificate' => ['Bola tug\'ilganlik guvohnomasi', 'Bolalar bo\'lsa — majburiy. Notarial tarjima.'],
            'birth_certificate' => ['Ariza beruvchining tug\'ilganlik guvohnomasi', 'Tug\'ilganlik guvohnomasi.'],
            'criminal_record' => ['Sudlanmaganlik ma\'lumotnomasi', 'IIV dan sudlanmaganlik ma\'lumotnomasi.'],
            'old_passports' => ['Eski xorijiy pasportlar', 'Vizalar va shtamplar bilan barcha eski pasportlar.'],
            'previous_visas' => ['Oldingi vizalar nusxalari', 'Oldin olingan barcha vizalar nusxalari.'],
            'sponsor_letter' => ['Homiy xati', 'Sayohatni uchinchi shaxs to\'lasa.'],
            'id_card_copy' => ['ID-karta nusxasi', 'Ichki pasport yoki ID-karta nusxasi.'],
            'guarantor_letter' => ['Moliyaviy kafil xati', 'Moliyaviy kafilning xati.'],
            'guarantor_income' => ['Kafil daromadi haqida ma\'lumotnoma', 'Kafil daromadi haqida ma\'lumotnoma.'],
            'guarantor_bank_statement' => ['Kafil bank ko\'chirmasi', 'Kafil bank ko\'chirmasi.'],
            'residence_permit' => ['Yashash guvohnomasi / VNJ', 'Fuqarolik davlatida yashamasa.'],
            'visa_fee_receipt' => ['Viza yig\'imi to\'lovi kvitansiyasi', 'Konsullik yig\'imi to\'langanligi tasdig\'i.'],
            'labor_contract' => ['Mehnat shartnomasi', 'Mehnat shartnomasi — bandlikni qo\'shimcha tasdiqlash.'],
            'labor_book' => ['Mehnat daftarchasi', 'To\'liq mehnat tarixi.'],
            'business_registration' => ['YaTT/MChJ ro\'yxatga olish guvohnomasi', 'Tadbirkorlar uchun: ro\'yxatga olish guvohnomasi.'],
            'property_certificate' => ['Ko\'chmas mulk haqida ma\'lumotnoma (mygov.uz)', 'Mygov.uz orqali ko\'chmas mulk reestridan ko\'chirma.'],
            'car_registration' => ['Avtomobil texnik pasporti nusxasi', 'Texnik pasport — mulkchilik tasdig\'i.'],
            'family_composition' => ['Oila tarkibi haqida ma\'lumotnoma', 'Mahalladan oila tarkibi ma\'lumotnomasi.'],
            'invitation_letter' => ['Taklif xati / Mehmonxona bron', 'Bronlash yoki attestation d\'accueil tasdig\'i.'],
            'france_visas_form' => ['France-Visas anketa (bosma nusxa)', 'France-Visas portalidan to\'ldirilgan va chop etilgan anketa + imzo.'],
        ];

        foreach ($translations as $slug => [$nameUz, $descUz]) {
            DB::table('document_templates')
                ->where('slug', $slug)
                ->update(['name_uz' => $nameUz, 'description_uz' => $descUz]);
        }

        // Добавить name_uz в case_checklist для отображения на узбекском
        if (!Schema::hasTable('case_checklist')) return;
        Schema::table('case_checklist', function (Blueprint $table) {
            $table->string('name_uz')->nullable()->after('name');
            $table->text('description_uz')->nullable()->after('description');
        });

        // Заполнить из document_templates через country_visa_requirements (только PostgreSQL)
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                UPDATE case_checklist cl
                SET name_uz = dt.name_uz, description_uz = dt.description_uz
                FROM country_visa_requirements cvr
                JOIN document_templates dt ON dt.id = cvr.document_template_id
                WHERE cl.country_requirement_id = cvr.id
                  AND dt.name_uz IS NOT NULL
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('case_checklist') && Schema::hasColumn('case_checklist', 'name_uz')) {
            Schema::table('case_checklist', function (Blueprint $table) {
                $table->dropColumn(['name_uz', 'description_uz']);
            });
        }
        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropColumn(['name_uz', 'description_uz']);
        });
    }
};
