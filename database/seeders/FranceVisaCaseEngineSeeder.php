<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FranceVisaCaseEngineSeeder extends Seeder
{
    public function run(): void
    {
        // ============================================================
        // 1. RULE: France / tourist / short_stay / adult
        // ============================================================
        $ruleId = Str::uuid()->toString();

        DB::table('visa_case_rules')->insert([
            'id'                      => $ruleId,
            'country_code'            => 'FR',
            'visa_type'               => 'tourist',
            'visa_subtype'            => 'short_stay',
            'applicant_type'          => 'adult',
            'embassy_platform'        => 'france_visas',
            'submission_method'       => 'vfs',
            'appointment_required'    => true,
            'biometrics_required'     => true,
            'personal_visit_required' => true,
            'interview_possible'      => false,
            'processing_days_min'     => 5,
            'processing_days_max'     => 15,
            'consular_fee_eur'        => 80.00,
            'service_fee_eur'         => 43.50,
            'max_stay_days'           => 90,
            'validity_months'         => 6,
            'workflow_steps'          => json_encode([
                'identity', 'employment', 'previous_visa', 'stay_passport',
                'contacts_funding', 'documents',
            ]),
            'notes'                   => 'France Schengen C visa (short stay <= 90 days). Fill France-Visas portal, then book TLScontact.',
            'guidance'                => json_encode([
                // Stage-specific tips
                ['stage' => 'lead', 'title' => 'Проверьте паспорт клиента', 'description' => 'Паспорт должен быть действителен минимум 3 месяца после даты выезда из Шенгена. Минимум 2 чистые страницы.'],
                ['stage' => 'lead', 'title' => 'Уточните даты поездки', 'description' => 'Точные даты нужны для бронирования отеля, билетов и расчета страховки.'],

                ['stage' => 'in_progress', 'title' => 'Соберите все документы', 'description' => '8 обязательных документов. Начните со справки с работы и выписки из банка — они готовятся дольше всего.'],
                ['stage' => 'in_progress', 'title' => 'Проверьте страховку', 'description' => 'Покрытие 30 000 EUR, зона действия — весь Шенген, на весь период поездки + 15 дней запас.', 'warning' => 'Без страховки заявку не примут.'],

                ['stage' => 'documents_review', 'title' => 'Заполните анкету France-Visas', 'description' => 'Откройте france-visas.gouv.fr, создайте аккаунт. Используйте Form Helper для копирования данных. Все текстовые поля — ЗАГЛАВНЫМИ БУКВАМИ.', 'warning' => 'Формат дат: DD/MM/YYYY (не YYYY-MM-DD).'],
                ['stage' => 'documents_review', 'title' => 'Получите reference number', 'description' => 'После заполнения анкеты на France-Visas скачайте PDF и запишите reference number. Он понадобится для записи в TLScontact.'],

                ['stage' => 'ready_to_submit', 'title' => 'Запишитесь в TLScontact', 'description' => 'Зайдите на tls.tlscontact.com, используйте reference number от France-Visas. Ближайшие слоты бывают через 1-3 недели.', 'warning' => 'Без reference number запись невозможна.'],
                ['stage' => 'ready_to_submit', 'title' => 'Подготовьте пакет документов', 'description' => 'Распечатайте анкету (подпись клиента), фото 3.5x4.5 (2 шт), оригиналы и копии всех документов. Документы — в папке по порядку.'],
                ['stage' => 'ready_to_submit', 'title' => 'Оплата сборов', 'description' => 'Консульский сбор 80 EUR + сервисный сбор TLS 43.50 EUR. Оплата на месте при подаче.'],

                ['stage' => 'submitted', 'title' => 'Ожидание результата', 'description' => 'Срок рассмотрения 5-15 рабочих дней. Отслеживайте статус на tls.tlscontact.com. SMS уведомление придет при готовности.'],

                // General tips (no stage)
                ['stage' => null, 'title' => 'Порядок работы: France-Visas -> TLScontact', 'description' => 'СНАЧАЛА заполните анкету на France-Visas и получите reference number. ПОТОМ запишитесь в TLScontact. Не наоборот.'],
                ['stage' => null, 'title' => 'Все текстовые поля — UPPERCASE', 'description' => 'France-Visas требует заполнения всех текстовых полей заглавными буквами.'],
                ['stage' => null, 'title' => 'Формат дат: DD/MM/YYYY', 'description' => 'На портале France-Visas даты указываются в европейском формате.'],
                ['stage' => null, 'title' => 'Биометрия обязательна', 'description' => 'Клиент должен лично явиться в TLScontact для сдачи отпечатков пальцев. Если биометрия сдавалась менее 59 месяцев назад — повторная сдача не требуется.'],
            ]),
            'is_active'               => true,
            'effective_from'          => '2026-01-01',
            'created_at'              => now(),
            'updated_at'              => now(),
        ]);

        // ============================================================
        // 2. REQUIRED DOCUMENTS
        // ============================================================
        $docs = [
            ['name' => 'Паспорт (оригинал + копия всех страниц)', 'description' => 'Действителен минимум 3 мес после выезда. 2+ чистые страницы.', 'requirement_level' => 'required', 'display_order' => 1],
            ['name' => 'Фото 3.5x4.5 (2 шт)', 'description' => 'Белый фон, без очков и головных уборов, давность не более 6 мес.', 'requirement_level' => 'required', 'display_order' => 2],
            ['name' => 'Анкета France-Visas (распечатка)', 'description' => 'Заполненная и распечатанная анкета с портала France-Visas + подпись.', 'requirement_level' => 'required', 'display_order' => 3],
            ['name' => 'Бронь отеля / Приглашение', 'description' => 'Подтверждение бронирования или attestation d\'accueil.', 'requirement_level' => 'required', 'display_order' => 4],
            ['name' => 'Авиабилеты (бронь)', 'description' => 'Бронь билетов туда-обратно.', 'requirement_level' => 'required', 'display_order' => 5],
            ['name' => 'Медицинская страховка', 'description' => 'Покрытие 30 000 EUR, зона Шенген, на весь период поездки.', 'requirement_level' => 'required', 'display_order' => 6],
            ['name' => 'Справка с работы', 'description' => 'На бланке организации: должность, стаж, оклад. Давность не более 1 мес.', 'requirement_level' => 'required', 'display_order' => 7],
            ['name' => 'Выписка из банка (3 мес)', 'description' => 'Выписка за последние 3 месяца, заверенная банком.', 'requirement_level' => 'required', 'display_order' => 8],
            ['name' => 'Копия ID-карты', 'description' => 'Копия внутреннего удостоверения личности.', 'requirement_level' => 'recommended', 'display_order' => 9],
            ['name' => 'Свидетельство о регистрации ИП/ООО', 'description' => 'Для предпринимателей: свидетельство или выписка из реестра.', 'requirement_level' => 'conditional', 'condition_description' => 'Только для предпринимателей/директоров', 'display_order' => 10],
            ['name' => 'Спонсорское письмо', 'description' => 'Если поездку оплачивает третье лицо.', 'requirement_level' => 'conditional', 'condition_description' => 'Если поездку оплачивает спонсор', 'display_order' => 11],
        ];

        foreach ($docs as $doc) {
            DB::table('visa_case_required_documents')->insert(array_merge([
                'id'                 => Str::uuid()->toString(),
                'visa_case_rule_id'  => $ruleId,
                'document_template_id' => null,
                'applicant_types'    => null,
                'accepted_formats'   => 'pdf,jpg,png',
                'requires_translation' => false,
                'min_validity_rule'  => null,
                'notes'              => null,
                'is_active'          => true,
                'created_at'         => now(),
                'updated_at'         => now(),
            ], $doc));
        }

        // ============================================================
        // 3. CHECKPOINTS
        // ============================================================
        $checkpoints = [
            // qualification
            ['stage' => 'qualification', 'slug' => 'passport_valid', 'title' => 'Паспорт проверен (действителен >3 мес после выезда)', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 1],
            ['stage' => 'qualification', 'slug' => 'photo_ready', 'title' => 'Фото 3.5x4.5 готово', 'check_type' => 'manual', 'is_blocking' => false, 'display_order' => 2],
            ['stage' => 'qualification', 'slug' => 'travel_dates_confirmed', 'title' => 'Даты поездки подтверждены', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 3],

            // documents
            ['stage' => 'documents', 'slug' => 'all_required_docs', 'title' => 'Все обязательные документы загружены', 'check_type' => 'auto_document', 'is_blocking' => true, 'display_order' => 1],
            ['stage' => 'documents', 'slug' => 'hotel_booking', 'title' => 'Бронь отеля подтверждена', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 2],
            ['stage' => 'documents', 'slug' => 'flight_booking', 'title' => 'Авиабилеты забронированы', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 3],
            ['stage' => 'documents', 'slug' => 'insurance_valid', 'title' => 'Страховка оформлена (30 000 EUR, Шенген)', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 4],

            // translation
            ['stage' => 'translation', 'slug' => 'docs_translated', 'title' => 'Документы переведены (если требуется)', 'check_type' => 'manual', 'is_blocking' => false, 'display_order' => 1],

            // ready
            ['stage' => 'ready', 'slug' => 'france_visas_filled', 'title' => 'Анкета France-Visas заполнена', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 1],
            ['stage' => 'ready', 'slug' => 'reference_number', 'title' => 'Получен reference number France-Visas', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 2],
            ['stage' => 'ready', 'slug' => 'tls_appointment', 'title' => 'Запись в TLScontact получена', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 3],
            ['stage' => 'ready', 'slug' => 'consular_fee_paid', 'title' => 'Консульский сбор оплачен', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 4],

            // review (submitted to embassy)
            ['stage' => 'review', 'slug' => 'docs_submitted_vfs', 'title' => 'Документы поданы в VFS/TLS', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 1],
            ['stage' => 'review', 'slug' => 'biometrics_done', 'title' => 'Биометрия сдана', 'check_type' => 'manual', 'is_blocking' => true, 'display_order' => 2],
        ];

        foreach ($checkpoints as $cp) {
            DB::table('visa_case_checkpoints')->insert(array_merge([
                'id'                => Str::uuid()->toString(),
                'visa_case_rule_id' => $ruleId,
                'description'       => null,
                'auto_check_config' => null,
                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ], $cp));
        }

        // ============================================================
        // 4. FORM FIELD MAPPINGS (~60 полей France-Visas)
        // ============================================================
        $fields = [
            // --- Step 1: Your identity ---
            ['step_number' => 1, 'step_title' => 'Your identity', 'field_key' => 'surname', 'field_label' => 'Surname(s) / Name(s)', 'field_type' => 'text', 'mapping_source' => 'client.last_name', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 1],
            ['step_number' => 1, 'step_title' => 'Your identity', 'field_key' => 'first_name', 'field_label' => 'First name(s)', 'field_type' => 'text', 'mapping_source' => 'client.first_name', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 2],
            ['step_number' => 1, 'step_title' => 'Your identity', 'field_key' => 'birth_date', 'field_label' => 'Date of birth', 'field_type' => 'date', 'mapping_source' => 'client.dob', 'transform_rule' => 'date_dmy', 'is_required' => true, 'display_order' => 3],
            ['step_number' => 1, 'step_title' => 'Your identity', 'field_key' => 'birth_place', 'field_label' => 'Place of birth', 'field_type' => 'text', 'mapping_source' => 'client.place_of_birth', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 4],
            ['step_number' => 1, 'step_title' => 'Your identity', 'field_key' => 'birth_country', 'field_label' => 'Country of birth', 'field_type' => 'select', 'mapping_source' => 'client.country_of_birth', 'transform_rule' => 'country_name', 'is_required' => true, 'display_order' => 5],
            ['step_number' => 1, 'step_title' => 'Your identity', 'field_key' => 'nationality', 'field_label' => 'Current nationality', 'field_type' => 'select', 'mapping_source' => 'client.nationality', 'transform_rule' => 'country_name', 'is_required' => true, 'display_order' => 6],
            ['step_number' => 1, 'step_title' => 'Your identity', 'field_key' => 'nationality_at_birth', 'field_label' => 'Nationality at birth', 'field_type' => 'select', 'mapping_source' => 'client.nationality', 'transform_rule' => 'country_name', 'is_required' => true, 'display_order' => 7],
            ['step_number' => 1, 'step_title' => 'Your identity', 'field_key' => 'sex', 'field_label' => 'Sex', 'field_type' => 'radio', 'options' => json_encode(['M' => 'Male', 'F' => 'Female']), 'mapping_source' => 'client.gender', 'is_required' => true, 'display_order' => 8],
            ['step_number' => 1, 'step_title' => 'Your identity', 'field_key' => 'marital_status', 'field_label' => 'Marital status', 'field_type' => 'select', 'options' => json_encode(['single' => 'Single', 'married' => 'Married', 'divorced' => 'Divorced', 'widowed' => 'Widowed', 'separated' => 'Separated']), 'mapping_source' => 'client.marital_status', 'is_required' => true, 'display_order' => 9],
            ['step_number' => 1, 'step_title' => 'Your identity', 'field_key' => 'national_id', 'field_label' => 'National identity number', 'field_type' => 'text', 'mapping_source' => 'client.national_id', 'is_required' => false, 'display_order' => 10],

            // --- Step 2: Your job ---
            ['step_number' => 2, 'step_title' => 'Your job', 'field_key' => 'occupation', 'field_label' => 'Current occupation', 'field_type' => 'text', 'mapping_source' => 'client.job_title', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 1],
            ['step_number' => 2, 'step_title' => 'Your job', 'field_key' => 'job_sector', 'field_label' => 'Sector of activity', 'field_type' => 'select', 'options' => json_encode([
                'accommodation_food' => 'Accommodation and food services',
                'admin_support' => 'Administrative and support services',
                'agriculture' => 'Agriculture, forestry and fishing',
                'arts_entertainment' => 'Arts, entertainment and recreation',
                'construction' => 'Construction',
                'education' => 'Education',
                'electricity_gas' => 'Electricity, gas, steam, air conditioning',
                'financial_insurance' => 'Financial and insurance',
                'health_social' => 'Human health and social work',
                'information_communication' => 'Information and communication',
                'manufacturing' => 'Manufacturing',
                'mining' => 'Mining and quarrying',
                'other' => 'Other activities',
                'professional_scientific' => 'Professional, scientific and technical',
                'public_admin' => 'Public administration and defence',
                'real_estate' => 'Real estate',
                'transportation' => 'Transportation and storage',
                'water_supply' => 'Water supply, sewerage, waste management',
                'wholesale_retail' => 'Wholesale and retail trade',
            ]), 'is_required' => true, 'display_order' => 2],
            ['step_number' => 2, 'step_title' => 'Your job', 'field_key' => 'eu_family_member', 'field_label' => 'Family member of EU/EEA citizen travelling', 'field_type' => 'radio', 'options' => json_encode(['yes' => 'Yes', 'no' => 'No']), 'is_required' => true, 'display_order' => 3, 'help_text' => 'Есть ли среди путешествующих член семьи, являющийся гражданином ЕС/ЕЭП'],
            ['step_number' => 2, 'step_title' => 'Your job', 'field_key' => 'employer_name', 'field_label' => 'Employer name', 'field_type' => 'text', 'mapping_source' => 'client.employer_name', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 4],
            ['step_number' => 2, 'step_title' => 'Your job', 'field_key' => 'employer_address', 'field_label' => 'Employer address', 'field_type' => 'text', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 5],
            ['step_number' => 2, 'step_title' => 'Your job', 'field_key' => 'employer_zip', 'field_label' => 'Post code', 'field_type' => 'text', 'is_required' => true, 'display_order' => 6],
            ['step_number' => 2, 'step_title' => 'Your job', 'field_key' => 'employer_city', 'field_label' => 'City', 'field_type' => 'text', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 7],
            ['step_number' => 2, 'step_title' => 'Your job', 'field_key' => 'employer_country', 'field_label' => 'Country', 'field_type' => 'select', 'transform_rule' => 'country_name', 'is_required' => true, 'display_order' => 8],
            ['step_number' => 2, 'step_title' => 'Your job', 'field_key' => 'employer_phone', 'field_label' => 'Telephone', 'field_type' => 'text', 'is_required' => true, 'display_order' => 9],
            ['step_number' => 2, 'step_title' => 'Your job', 'field_key' => 'employer_email', 'field_label' => 'Email', 'field_type' => 'text', 'is_required' => true, 'display_order' => 10],

            // --- Step 3: Your last visa ---
            ['step_number' => 3, 'step_title' => 'Your last visa', 'field_key' => 'has_previous_schengen', 'field_label' => 'Previous Schengen visa', 'field_type' => 'radio', 'options' => json_encode(['yes' => 'Yes', 'no' => 'No']), 'is_required' => true, 'display_order' => 1],
            ['step_number' => 3, 'step_title' => 'Your last visa', 'field_key' => 'prev_visa_from', 'field_label' => 'Valid from', 'field_type' => 'date', 'transform_rule' => 'date_dmy', 'is_required' => false, 'display_order' => 2, 'help_text' => 'Заполнять если была предыдущая Шенген виза'],
            ['step_number' => 3, 'step_title' => 'Your last visa', 'field_key' => 'prev_visa_until', 'field_label' => 'Valid until', 'field_type' => 'date', 'transform_rule' => 'date_dmy', 'is_required' => false, 'display_order' => 3],
            ['step_number' => 3, 'step_title' => 'Your last visa', 'field_key' => 'fingerprints_collected', 'field_label' => 'Fingerprints collected', 'field_type' => 'radio', 'options' => json_encode(['yes' => 'Yes', 'no' => 'No']), 'is_required' => true, 'display_order' => 4],
            ['step_number' => 3, 'step_title' => 'Your last visa', 'field_key' => 'fingerprints_date', 'field_label' => 'Date of fingerprints', 'field_type' => 'date', 'transform_rule' => 'date_dmy', 'is_required' => false, 'display_order' => 5],
            ['step_number' => 3, 'step_title' => 'Your last visa', 'field_key' => 'biometric_visa_number', 'field_label' => 'Biometric visa number', 'field_type' => 'text', 'is_required' => false, 'display_order' => 6, 'help_text' => 'Формат: 3 буквы + 9 цифр (пр. ESP020909681)'],

            // --- Step 4: Your stay ---
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'purpose_of_stay', 'field_label' => 'Purpose of stay', 'field_type' => 'select', 'options' => json_encode([
                'tourism' => 'Tourism', 'business' => 'Business', 'family_visit' => 'Family/private visit',
                'official' => 'Official', 'study' => 'Study', 'medical' => 'Medical',
                'entry_visa' => 'Entry visa', 'familial' => 'Familial', 'other' => 'Other',
            ]), 'is_required' => true, 'display_order' => 1],
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'entry_date', 'field_label' => 'Date of arrival', 'field_type' => 'date', 'mapping_source' => 'case.travel_date', 'transform_rule' => 'date_dmy', 'is_required' => true, 'display_order' => 2],
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'exit_date', 'field_label' => 'Date of departure', 'field_type' => 'date', 'mapping_source' => 'case.return_date', 'transform_rule' => 'date_dmy', 'is_required' => true, 'display_order' => 3],
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'number_of_entries', 'field_label' => 'Number of entries', 'field_type' => 'select', 'options' => json_encode(['1' => '1', '2' => '2', 'multiple' => 'Multiple']), 'is_required' => true, 'display_order' => 4],
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'stays_per_year', 'field_label' => 'Number of stays per year', 'field_type' => 'select', 'options' => json_encode(['1' => '1', '2' => '2', '3' => '3', 'more' => 'More']), 'is_required' => true, 'display_order' => 5],

            // passport in Step 4
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'travel_doc_type', 'field_label' => 'Travel document type', 'field_type' => 'select', 'options' => json_encode(['ordinary' => 'Ordinary passport', 'diplomatic' => 'Diplomatic passport', 'service' => 'Service passport', 'official' => 'Official passport']), 'is_required' => true, 'display_order' => 6],
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'passport_number', 'field_label' => 'Travel document number', 'field_type' => 'text', 'mapping_source' => 'client.passport_number', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 7],
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'passport_issue_date', 'field_label' => 'Date of issue', 'field_type' => 'date', 'mapping_source' => 'client.passport_issue_date', 'transform_rule' => 'date_dmy', 'is_required' => true, 'display_order' => 8],
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'passport_expiry_date', 'field_label' => 'Valid until', 'field_type' => 'date', 'mapping_source' => 'client.passport_expiry_date', 'transform_rule' => 'date_dmy', 'is_required' => true, 'display_order' => 9, 'help_text' => 'Должен быть действителен минимум 3 мес после даты выезда'],
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'passport_issued_by', 'field_label' => 'Issued by', 'field_type' => 'text', 'mapping_source' => 'client.passport_issued_by', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 10],
            ['step_number' => 4, 'step_title' => 'Your stay', 'field_key' => 'passport_issue_country', 'field_label' => 'Country of issue', 'field_type' => 'select', 'transform_rule' => 'country_name', 'is_required' => true, 'display_order' => 11],

            // --- Step 5: Your contacts ---
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'accommodation_type', 'field_label' => 'Type of accommodation', 'field_type' => 'radio', 'options' => json_encode(['hotel' => 'Hotel or organisation', 'person' => 'Person']), 'is_required' => true, 'display_order' => 1],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'hotel_name', 'field_label' => 'Hotel name', 'field_type' => 'text', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 2, 'help_text' => 'Заполнять если тип проживания = Hotel'],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'hotel_address', 'field_label' => 'Hotel address', 'field_type' => 'text', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 3],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'hotel_zip', 'field_label' => 'Post code', 'field_type' => 'text', 'is_required' => true, 'display_order' => 4],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'hotel_city', 'field_label' => 'City', 'field_type' => 'text', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 5],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'hotel_country', 'field_label' => 'Country', 'field_type' => 'select', 'transform_rule' => 'country_name', 'is_required' => true, 'display_order' => 6],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'hotel_phone', 'field_label' => 'Hotel telephone', 'field_type' => 'text', 'is_required' => false, 'display_order' => 7],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'hotel_email', 'field_label' => 'Hotel email', 'field_type' => 'text', 'is_required' => false, 'display_order' => 8],

            // Funding
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'funding_source', 'field_label' => 'Who funds the travel', 'field_type' => 'radio', 'options' => json_encode(['myself' => 'Myself', 'sponsor' => 'Sponsor']), 'is_required' => true, 'display_order' => 9],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'means_of_support', 'field_label' => 'Means of support', 'field_type' => 'checkbox', 'options' => json_encode([
                'credit_card' => 'Credit card', 'cash' => 'Cash',
                'traveller_cheques' => 'Traveller\'s cheques',
                'prepaid_accommodation' => 'Pre-paid accommodation',
                'prepaid_transport' => 'Pre-paid transport', 'other' => 'Other',
            ]), 'is_required' => true, 'display_order' => 10],

            // Person completing the form
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'form_filler_name', 'field_label' => 'Person completing: Name(s)', 'field_type' => 'text', 'mapping_source' => 'client.last_name', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 11],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'form_filler_first_name', 'field_label' => 'Person completing: First name(s)', 'field_type' => 'text', 'mapping_source' => 'client.first_name', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 12],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'form_filler_address', 'field_label' => 'Person completing: Address', 'field_type' => 'text', 'mapping_source' => 'client.address', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 13],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'form_filler_zip', 'field_label' => 'Person completing: Post code', 'field_type' => 'text', 'is_required' => true, 'display_order' => 14],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'form_filler_city', 'field_label' => 'Person completing: City', 'field_type' => 'text', 'transform_rule' => 'uppercase', 'is_required' => true, 'display_order' => 15],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'form_filler_country', 'field_label' => 'Person completing: Country', 'field_type' => 'select', 'transform_rule' => 'country_name', 'is_required' => true, 'display_order' => 16],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'form_filler_phone', 'field_label' => 'Person completing: Telephone', 'field_type' => 'text', 'mapping_source' => 'client.phone', 'is_required' => true, 'display_order' => 17],
            ['step_number' => 5, 'step_title' => 'Your contacts', 'field_key' => 'form_filler_email', 'field_label' => 'Person completing: Email', 'field_type' => 'text', 'mapping_source' => 'client.email', 'is_required' => true, 'display_order' => 18],
        ];

        foreach ($fields as $field) {
            $row = [
                'id'                => Str::uuid()->toString(),
                'visa_case_rule_id' => $ruleId,
                'step_number'       => $field['step_number'],
                'step_title'        => $field['step_title'],
                'field_key'         => $field['field_key'],
                'field_label'       => $field['field_label'],
                'field_type'        => $field['field_type'],
                'options'           => $field['options'] ?? null,
                'default_value'     => $field['default_value'] ?? null,
                'mapping_source'    => $field['mapping_source'] ?? null,
                'transform_rule'    => $field['transform_rule'] ?? null,
                'help_text'         => $field['help_text'] ?? null,
                'validation_rules'  => null,
                'is_required'       => $field['is_required'],
                'display_order'     => $field['display_order'],
                'is_active'         => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];

            DB::table('visa_form_field_mappings')->insert($row);
        }
    }
}
