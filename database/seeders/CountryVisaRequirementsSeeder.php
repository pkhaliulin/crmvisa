<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CountryVisaRequirementsSeeder extends Seeder
{
    public function run(): void
    {
        $templates = DB::table('document_templates')->pluck('id', 'slug');

        // Формат: [country, visa_type, slug, level, notes, override, order, ai_country_notes, target_audience?]
        // level: required | recommended | confirmation_only
        // target_audience: applicant (default) | family_all | family_spouse | family_child | family_minor | family_parent | both

        $requirements = array_merge(
            $this->universal(),
            $this->universalFamily(),
            $this->schengenCommon('FR'),
            $this->schengenCommon('DE'),
            $this->schengenCommon('ES'),
            $this->schengenCommon('IT'),
            $this->schengenCommon('PL'),
            $this->schengenCommon('CZ'),
            $this->schengenFamilyMinor(),
            $this->schengenFamilySpouse(),
            $this->franceTourist(),
            $this->franceBusiness(),
            $this->franceStudent(),
            $this->germanyTourist(),
            $this->germanyBusiness(),
            $this->germanyStudent(),
            $this->spainTourist(),
            $this->spainBusiness(),
            $this->spainStudent(),
            $this->italyTourist(),
            $this->italyBusiness(),
            $this->italyStudent(),
            $this->polandTourist(),
            $this->polandBusiness(),
            $this->czechTourist(),
            $this->czechBusiness(),
            $this->usaTourist(),
            $this->usaBusiness(),
            $this->usaStudent(),
            $this->ukTourist(),
            $this->ukBusiness(),
            $this->ukStudent(),
            $this->canadaTourist(),
            $this->canadaBusiness(),
            $this->canadaStudent(),
            $this->australiaTourist(),
            $this->australiaStudent(),
            $this->uaeTourist(),
            $this->uaeBusiness(),
            $this->koreaTourist(),
            $this->koreaBusiness(),
            $this->koreaStudent(),
            $this->japanTourist(),
            $this->japanBusiness(),
            $this->japanStudent(),
            $this->turkeyTourist(),
        );

        foreach ($requirements as $row) {
            [$cc, $vt, $slug, $level, $notes, $override, $order, $aiNotes] = $row;
            $targetAudience = $row[8] ?? 'applicant';

            $templateId = $templates[$slug] ?? null;
            if (!$templateId) {
                $this->command?->warn("Шаблон не найден: {$slug}");
                continue;
            }

            $hasAiColumns = \Illuminate\Support\Facades\Schema::hasColumn('country_visa_requirements', 'ai_country_notes');
            $hasTargetAudience = \Illuminate\Support\Facades\Schema::hasColumn('country_visa_requirements', 'target_audience');

            $data = [
                'id'                   => (string) Str::uuid(),
                'country_code'         => $cc,
                'visa_type'            => $vt,
                'document_template_id' => $templateId,
                'requirement_level'    => $level,
                'notes'                => $notes,
                'override_metadata'    => $override ? json_encode($override) : null,
                'display_order'        => $order,
                'is_active'            => true,
                'effective_from'       => null,
                'effective_to'         => null,
                'created_at'           => now(),
                'updated_at'           => now(),
            ];

            $uniqueKeys = ['country_code', 'visa_type', 'document_template_id'];
            $updateFields = ['requirement_level', 'notes', 'override_metadata', 'display_order', 'updated_at'];

            if ($hasTargetAudience) {
                $data['target_audience'] = $targetAudience;
                $uniqueKeys[] = 'target_audience';
                $updateFields[] = 'target_audience';
            }

            if ($hasAiColumns && $aiNotes) {
                $data['ai_country_notes'] = $aiNotes;
                $updateFields[] = 'ai_country_notes';
            }

            DB::table('country_visa_requirements')->upsert(
                $data,
                $uniqueKeys,
                $updateFields
            );
        }
    }

    // ================================================================
    // УНИВЕРСАЛЬНЫЕ — для всех стран (* = любая)
    // ================================================================
    private function universal(): array
    {
        return [
            ['*', '*', 'foreign_passport',         'required',           'Действителен минимум 3 мес после выезда. 2+ чистые страницы.', null, 1, null],
            ['*', '*', 'internal_passport',        'required',           'Скан всех заполненных страниц: прописка, семейное положение, дети.', null, 2, null],
            ['*', '*', 'photo_3x4',                'confirmation_only',  'Белый фон, без очков и головных уборов, давность не более 6 мес.', null, 3, null],
            ['*', '*', 'application_form',         'confirmation_only',  'Заполнить и подписать анкету по форме посольства. Менеджер выдаст бланк.', null, 4, null],
            ['*', '*', 'income_certificate',       'required',           'Справка с места работы с указанием должности, оклада и стажа (не старше 1 мес.). Для ИП — налоговая декларация.', null, 5, null],
            ['*', '*', 'bank_balance_certificate', 'required',           'Официальная справка из банка с текущим остатком на счёте. Заверенная печатью банка, не старше 1 мес.', null, 6, null],
            ['*', '*', 'bank_statement',           'required',           'Выписка транзакций по банковской карте за последние 3-6 месяцев. Показывает регулярные доходы и расходы.', null, 7, null],
            ['*', '*', 'marriage_certificate',     'recommended',        'Если в браке — обязательно.', null, 8, null],
            ['*', '*', 'child_birth_certificate',  'recommended',        'Если есть дети — обязательно. Свидетельство о рождении ребёнка. Нотариальный перевод.', null, 9, true],
            ['*', '*', 'property_certificate',     'recommended',        'Выписка из Государственного реестра недвижимости через портал my.gov.uz.', null, 10, null],
            ['*', '*', 'car_registration',         'recommended',        'Копия техпаспорта — подтверждение собственности.', null, 11, null],
            ['*', '*', 'criminal_record',          'recommended',        'Справка о несудимости из МВД.', null, 12, null],
            ['*', '*', 'old_passports',            'recommended',        'Все старые загранпаспорта с визами — сильный плюс для визовой истории.', null, 13, null],
            ['*', '*', 'previous_visa_copies',     'recommended',        'Копии всех ранее полученных виз.', null, 14, null],
            ['*', '*', 'work_contract',            'recommended',        'Трудовой договор — дополнительное подтверждение занятости.', null, 15, null],
            ['*', '*', 'work_book',                'recommended',        'Полная история занятости.', null, 16, null],
            ['*', '*', 'business_ownership',       'recommended',        'Для владельцев бизнеса: свидетельство о регистрации ИП/ООО.', null, 17, null],
            ['*', '*', 'family_composition',       'recommended',        'Справка о составе семьи из махалли.', null, 18, null],
            ['*', '*', 'residence_permit',         'recommended',        'Если проживает не в стране гражданства.', null, 19, null],
        ];
    }

    // ================================================================
    // УНИВЕРСАЛЬНЫЕ — документы для членов семьи
    // ================================================================
    private function universalFamily(): array
    {
        return [
            // Для ВСЕХ членов семьи — паспорт и фото
            ['*', '*', 'foreign_passport',         'required',           'Паспорт члена семьи. Действителен минимум 3 мес после выезда.', null, 1, null, 'family_all'],
            ['*', '*', 'photo_3x4',                'confirmation_only',  'Фото члена семьи 3.5x4.5 на белом фоне.', null, 2, null, 'family_all'],
            ['*', '*', 'internal_passport',        'recommended',        'Внутренний паспорт члена семьи (скан).', null, 3, null, 'family_all'],

            // Для СУПРУГА — свидетельство о браке
            ['*', '*', 'marriage_certificate',     'required',           'Свидетельство о заключении брака. Нотариальный перевод обязателен.', null, 4, null, 'family_spouse'],

            // Для ДЕТЕЙ — свидетельство о рождении
            ['*', '*', 'child_birth_certificate',  'required',           'Свидетельство о рождении ребёнка. Нотариальный перевод.', null, 4, null, 'family_child'],

            // Для НЕСОВЕРШЕННОЛЕТНИХ — согласие на выезд + свидетельство о рождении
            ['*', '*', 'parental_consent',         'required',           'Нотариальное согласие на выезд от обоих родителей (если едет с одним). Обязательно для несовершеннолетних.', null, 5, null, 'family_minor'],
            ['*', '*', 'child_birth_certificate',  'required',           'Свидетельство о рождении (подтверждение родства). Нотариальный перевод.', null, 4, null, 'family_minor'],

            // Для РОДИТЕЛЕЙ — документ подтверждающий родство
            ['*', '*', 'child_birth_certificate',  'required',           'Свидетельство о рождении заявителя (подтверждение родства с родителем).', null, 4, null, 'family_parent'],
        ];
    }

    // ================================================================
    // ШЕНГЕН — общие для всех стран Шенгена (tourist + business)
    // ================================================================
    private function schengenCommon(string $cc): array
    {
        $items = [];
        foreach (['tourist', 'business'] as $vt) {
            $items[] = [$cc, $vt, 'travel_insurance', 'required', 'Покрытие 30 000 EUR, зона Шенген, на весь период поездки. Включая репатриацию.', ['min_coverage_eur' => 30000], 20, "Проверить: зона покрытия = Schengen, сумма >= 30 000 EUR, репатриация включена."];
            $items[] = [$cc, $vt, 'hotel_booking', 'required', 'Подтверждение бронирования на весь период пребывания. Или attestation d\'hébergement при проживании у частного лица.', null, 21, null];
            $items[] = [$cc, $vt, 'air_tickets', 'required', 'Бронь билетов туда-обратно. Допустима стыковка.', null, 22, null];
            $items[] = [$cc, $vt, 'employment_certificate', 'required', 'Справка с места работы на бланке компании: должность, стаж, зарплата, одобрение отпуска/командировки.', null, 23, 'Отдельный документ от справки о доходах. Обязателен для Шенгена.'];
        }
        return $items;
    }

    // ================================================================
    // ШЕНГЕН — страховка для несовершеннолетних членов семьи
    // ================================================================
    private function schengenFamilyMinor(): array
    {
        $schengenCountries = ['FR', 'DE', 'ES', 'IT', 'PL', 'CZ'];
        $visaTypes = ['tourist', 'business'];
        $items = [];

        foreach ($schengenCountries as $cc) {
            foreach ($visaTypes as $vt) {
                $items[] = [$cc, $vt, 'travel_insurance', 'required', 'Страховка для несовершеннолетнего. Минимум 30 000 EUR, зона Шенген.', ['min_coverage_eur' => 30000], 6, null, 'family_minor'];
            }
        }

        // UK — TB тест для семьи
        foreach (['tourist', 'business', 'student'] as $vt) {
            $items[] = ['GB', $vt, 'tb_test', 'required', 'TB тест для члена семьи. Обязателен для граждан Узбекистана.', null, 6, null, 'family_all'];
        }

        return $items;
    }

    // ================================================================
    // ШЕНГЕН — документы для супруга (дополнительно к universal)
    // ================================================================
    private function schengenFamilySpouse(): array
    {
        $schengenCountries = ['FR', 'DE', 'ES', 'IT', 'PL', 'CZ'];
        $items = [];

        foreach ($schengenCountries as $cc) {
            foreach (['tourist', 'business'] as $vt) {
                $items[] = [$cc, $vt, 'travel_insurance', 'required', 'Страховка для супруга/и. Минимум 30 000 EUR, зона Шенген.', ['min_coverage_eur' => 30000], 6, null, 'family_spouse'];
            }
        }

        return $items;
    }

    // ================================================================
    // ФРАНЦИЯ — tourist
    // ================================================================
    private function franceTourist(): array
    {
        return [
            ['FR', 'tourist', 'itinerary',              'recommended', 'Маршрутный лист по дням.', null, 23, null],
            ['FR', 'tourist', 'cover_letter',            'recommended', 'Рекомендуется для первого Шенгена.', null, 24, null],
            ['FR', 'tourist', 'invitation_letter_private', 'recommended', 'Если проживание у частного лица — attestation d\'accueil заверяется мэрией.', null, 25, 'Для Франции: attestation d\'accueil обязательно заверяется mairie.'],
            ['FR', 'tourist', 'attestation_accueil',     'recommended', 'Если проживание у частного лица. Оригинал из мэрии (mairie).', null, 26, null],
            ['FR', 'tourist', 'guarantor_letter',        'recommended', 'Письмо финансового гаранта.', null, 27, null],
            ['FR', 'tourist', 'guarantor_income',        'recommended', 'Справка о доходах гаранта.', null, 28, null],
            ['FR', 'tourist', 'guarantor_bank_statement', 'recommended', 'Выписка из банка гаранта за 3 мес.', ['period_months' => 3], 29, null],
        ];
    }

    // ================================================================
    // ФРАНЦИЯ — business
    // ================================================================
    private function franceBusiness(): array
    {
        return [
            ['FR', 'business', 'business_invitation',   'required', 'Invitation/request letter с описанием цели, длительности и места встречи.', null, 23, 'Для Франции business: нужно точное описание активности + кто покрывает расходы.'],
            ['FR', 'business', 'company_registration',  'recommended', 'Свидетельство о регистрации компании.', null, 24, null],
            ['FR', 'business', 'conference_invitation',  'recommended', 'Admission cards на выставки и конференции.', null, 25, null],
            ['FR', 'business', 'company_financial_statements', 'recommended', 'Финансовая отчётность компании.', null, 26, null],
            ['FR', 'business', 'financial_guarantee_org', 'recommended', 'Engagement de la société d\'accueil — гарантия расходов от принимающей компании.', null, 27, null],
            ['FR', 'business', 'employer_leave_approval', 'recommended', 'Разрешение от работодателя на командировку.', null, 28, null],
            ['FR', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 29, null],
            ['FR', 'business', 'business_agenda',        'recommended', 'Программа встреч — повышает шансы.', null, 30, null],
            ['FR', 'business', 'event_ticket',           'recommended', 'Билет на конференцию/выставку.', null, 31, null],
            ['FR', 'business', 'commercial_relationship_proof', 'recommended', 'Договоры, инвойсы — история деловых отношений.', null, 32, null],
            ['FR', 'business', 'host_expense_guarantee', 'recommended', 'Engagement de prise en charge.', null, 33, null],
            ['FR', 'business', 'mission_order',          'recommended', 'Командировочное удостоверение.', null, 34, null],
        ];
    }

    // ================================================================
    // ФРАНЦИЯ — student
    // ================================================================
    private function franceStudent(): array
    {
        return [
            ['FR', 'student', 'university_acceptance',       'required', 'Attestation de pré-inscription / lettre d\'admission.', null, 20, 'Для Франции: нужен сертификат Campus France + attestation de pré-inscription.'],
            ['FR', 'student', 'campus_france_attestation',   'required', 'ОБЯЗАТЕЛЬНО — без attestation Campus France консульство не примет досье.', null, 21, 'СТОП если attestation отрицательный.'],
            ['FR', 'student', 'academic_transcript',         'required', 'Транскрипт оценок. Нотариальный перевод.', null, 22, null],
            ['FR', 'student', 'diploma',                     'required', 'Диплом с нотариальным переводом + апостиль.', null, 23, null],
            ['FR', 'student', 'language_certificate',        'required', 'DELF B2 / TCF для франкоязычных программ; IELTS/TOEFL для англоязычных.', null, 24, 'Минимум DELF B2 для программ на французском, IELTS 6.0 для англоязычных.'],
            ['FR', 'student', 'study_plan',                  'required', 'Мотивационное письмо — почему Франция, этот вуз, карьерный план.', null, 25, null],
            ['FR', 'student', 'travel_insurance',            'required', 'Медицинская страховка на период учёбы.', ['min_coverage_eur' => 30000], 26, null],
            ['FR', 'student', 'bank_balance_certificate',    'required', 'Минимум 615 EUR/мес на год (~7 380 EUR).', ['min_balance_usd' => 8000], 27, 'Франция: 615 EUR/мес минимум.'],
            ['FR', 'student', 'tuition_payment',             'recommended', 'Квитанция об оплате обучения.', null, 28, null],
            ['FR', 'student', 'student_housing',             'required', 'Attestation d\'hébergement или contrat de bail.', null, 29, null],
            ['FR', 'student', 'scholarship_proof',           'recommended', 'Стипендия Campus France / Erasmus — сильный плюс.', null, 30, null],
            ['FR', 'student', 'birth_certificate',           'required', 'Свидетельство о рождении. Нотариальный перевод.', null, 31, null],
            ['FR', 'student', 'cv_resume',                   'recommended', 'CV / резюме на французском или английском.', null, 32, null],
            ['FR', 'student', 'health_insurance_student',    'required', 'CVEC + LMDE/SMERRA или приватная страховка.', null, 33, null],
        ];
    }

    // ================================================================
    // ГЕРМАНИЯ — tourist
    // ================================================================
    private function germanyTourist(): array
    {
        return [
            ['DE', 'tourist', 'itinerary',              'recommended', 'Маршрутный лист по дням.', null, 23, null],
            ['DE', 'tourist', 'cover_letter',            'recommended', 'Рекомендуется для первого Шенгена.', null, 24, null],
            ['DE', 'tourist', 'verpflichtungserklaerung', 'recommended', 'Если проживание у частного лица — Verpflichtungserklärung из Ausländerbehörde.', null, 25, null],
            ['DE', 'tourist', 'invitation_letter_private', 'recommended', 'Приглашение от частного лица в Германии.', null, 26, null],
        ];
    }

    // ================================================================
    // ГЕРМАНИЯ — business
    // ================================================================
    private function germanyBusiness(): array
    {
        return [
            ['DE', 'business', 'business_invitation',   'required', 'Письмо от принимающей компании в Германии.', null, 23, 'Германия: может потребоваться Verpflichtungserklärung.'],
            ['DE', 'business', 'company_registration',  'recommended', 'Свидетельство о регистрации компании.', null, 24, null],
            ['DE', 'business', 'conference_invitation',  'recommended', 'Приглашение на конференцию/выставку.', null, 25, null],
            ['DE', 'business', 'employer_leave_approval', 'recommended', 'Разрешение от работодателя.', null, 26, null],
            ['DE', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 27, null],
            ['DE', 'business', 'business_agenda',        'recommended', 'Программа встреч.', null, 28, null],
            ['DE', 'business', 'verpflichtungserklaerung', 'recommended', 'Формальное обязательство — Ausländerbehörde. Заменяет финансовые гарантии.', null, 29, 'Германия: Verpflichtungserklärung заменяет финансовые гарантии заявителя.'],
        ];
    }

    // ================================================================
    // ГЕРМАНИЯ — student
    // ================================================================
    private function germanyStudent(): array
    {
        return [
            ['DE', 'student', 'university_acceptance',   'required', 'Zulassungsbescheid — решение о зачислении.', null, 20, 'Германия: Zulassungsbescheid от вуза обязателен.'],
            ['DE', 'student', 'aps_certificate',         'required', 'APS-Zertifikat обязателен для граждан Узбекистана.', null, 21, 'СТОП если APS отрицательный. Срок ожидания 4-8 недель.'],
            ['DE', 'student', 'blocked_account',         'required', 'Sperrkonto: минимум 11 904 EUR (992 EUR/мес с 2025).', ['min_balance_usd' => 13000], 22, 'Германия: Sperrkonto через Expatrio/Fintiba/Deutsche Bank. Сумма 11 904 EUR (992 EUR/мес).'],
            ['DE', 'student', 'academic_transcript',     'required', 'Транскрипт оценок. Нотариальный перевод.', null, 23, null],
            ['DE', 'student', 'diploma',                 'required', 'Апостиль + признание через anabin.', null, 24, 'Германия: проверить признание диплома на anabin.kmk.org.'],
            ['DE', 'student', 'language_certificate',    'required', 'TestDaF 4x4 или DSH-2 для немецкоязычных; IELTS 6.0+ для англоязычных.', null, 25, null],
            ['DE', 'student', 'study_plan',              'recommended', 'Мотивационное письмо.', null, 26, null],
            ['DE', 'student', 'travel_insurance',        'required', 'Немецкая медстраховка: TK, AOK, MAWISTA.', null, 27, 'Германия: обязательна немецкая медстраховка (не путевая).'],
            ['DE', 'student', 'health_insurance_student', 'required', 'Немецкая студенческая страховка обязательна.', null, 28, null],
            ['DE', 'student', 'tuition_payment',         'recommended', 'Семестровый взнос (Semesterbeitrag).', null, 29, null],
            ['DE', 'student', 'student_housing',         'recommended', 'Wohnheimzusage или Mietvertrag.', null, 30, null],
            ['DE', 'student', 'cv_resume',               'recommended', 'Формат Europass.', null, 31, null],
            ['DE', 'student', 'birth_certificate',       'required', 'Свидетельство о рождении. Нотариальный перевод.', null, 32, null],
        ];
    }

    // ================================================================
    // ИСПАНИЯ — tourist
    // ================================================================
    private function spainTourist(): array
    {
        return [
            ['ES', 'tourist', 'itinerary',              'recommended', 'Маршрутный лист по дням.', null, 23, null],
            ['ES', 'tourist', 'cover_letter',            'recommended', 'Рекомендуется для первого Шенгена.', null, 24, null],
            ['ES', 'tourist', 'guarantor_letter',        'recommended', 'Если есть спонсор поездки.', null, 25, null],
            ['ES', 'tourist', 'guarantor_income',        'recommended', 'Справка о доходах спонсора.', null, 26, null],
            ['ES', 'tourist', 'guarantor_bank_statement', 'recommended', 'Выписка из банка спонсора.', ['period_months' => 3], 27, null],
        ];
    }

    // ================================================================
    // ИСПАНИЯ — business
    // ================================================================
    private function spainBusiness(): array
    {
        return [
            ['ES', 'business', 'business_invitation',   'required', 'Приглашение от испанской компании.', null, 23, null],
            ['ES', 'business', 'company_registration',  'recommended', 'Свидетельство о регистрации компании.', null, 24, null],
            ['ES', 'business', 'conference_invitation',  'recommended', 'Приглашение на выставку/конференцию.', null, 25, null],
            ['ES', 'business', 'employer_leave_approval', 'recommended', 'Разрешение от работодателя.', null, 26, null],
            ['ES', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 27, null],
            ['ES', 'business', 'business_agenda',        'recommended', 'Программа деловых встреч.', null, 28, null],
        ];
    }

    // ================================================================
    // ИСПАНИЯ — student
    // ================================================================
    private function spainStudent(): array
    {
        return [
            ['ES', 'student', 'university_acceptance',   'required', 'Carta de admisión от университета.', null, 20, null],
            ['ES', 'student', 'academic_transcript',     'required', 'Транскрипт оценок.', null, 21, null],
            ['ES', 'student', 'diploma',                 'required', 'Апостиль обязателен.', null, 22, null],
            ['ES', 'student', 'language_certificate',    'required', 'DELE для испаноязычных; IELTS для англоязычных.', null, 23, null],
            ['ES', 'student', 'bank_balance_certificate', 'required', 'Минимум ~600 EUR/мес на период обучения.', ['min_balance_usd' => 7500], 24, null],
            ['ES', 'student', 'travel_insurance',        'required', 'Медицинская страховка.', ['min_coverage_eur' => 30000], 25, null],
            ['ES', 'student', 'student_housing',         'required', 'Подтверждение проживания в Испании.', null, 26, null],
            ['ES', 'student', 'health_insurance_student', 'required', 'Полная медстраховка на период учёбы.', null, 27, null],
            ['ES', 'student', 'tuition_payment',         'recommended', 'Квитанция об оплате обучения.', null, 28, null],
            ['ES', 'student', 'criminal_record',         'required', 'Справка о несудимости — обязательна для студенческой визы.', null, 29, null],
            ['ES', 'student', 'medical_certificate',     'required', 'Медицинская справка.', null, 30, null],
            ['ES', 'student', 'birth_certificate',       'required', 'Свидетельство о рождении.', null, 31, null],
        ];
    }

    // ================================================================
    // ИТАЛИЯ — tourist
    // ================================================================
    private function italyTourist(): array
    {
        return [
            ['IT', 'tourist', 'cover_letter',            'required', 'Covering letter с деталями о заявителе, поездке и попутчиках. Для Италии — обязателен.', null, 24, 'Италия: covering letter обязателен (в отличие от других Шенген-стран).'],
            ['IT', 'tourist', 'itinerary',              'recommended', 'Маршрутный лист по дням.', null, 25, null],
            ['IT', 'tourist', 'invitation_letter_private', 'recommended', 'Dichiarazione di ospitalità + копия паспорта хозяина + permesso di soggiorno.', null, 26, null],
        ];
    }

    // ================================================================
    // ИТАЛИЯ — business
    // ================================================================
    private function italyBusiness(): array
    {
        return [
            ['IT', 'business', 'business_invitation',   'required', 'Приглашение от итальянской компании.', null, 23, null],
            ['IT', 'business', 'company_registration',  'recommended', 'Свидетельство о регистрации компании.', null, 24, null],
            ['IT', 'business', 'conference_invitation',  'recommended', 'Приглашение на мероприятие.', null, 25, null],
            ['IT', 'business', 'employer_leave_approval', 'recommended', 'Разрешение от работодателя.', null, 26, null],
            ['IT', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 27, null],
            ['IT', 'business', 'financial_guarantee_org', 'recommended', 'Fidejussione bancaria — банковская гарантия от приглашающей стороны.', null, 28, 'Италия: fidejussione bancaria может заменить финансовые гарантии заявителя.'],
            ['IT', 'business', 'business_agenda',        'recommended', 'Программа встреч.', null, 29, null],
        ];
    }

    // ================================================================
    // ИТАЛИЯ — student
    // ================================================================
    private function italyStudent(): array
    {
        return [
            ['IT', 'student', 'university_acceptance',   'required', 'Lettera di ammissione от университета.', null, 20, null],
            ['IT', 'student', 'academic_transcript',     'required', 'Транскрипт оценок. Нотариальный перевод.', null, 21, null],
            ['IT', 'student', 'diploma',                 'required', 'Апостиль + Dichiarazione di Valore.', null, 22, 'Италия: нужен Dichiarazione di Valore от итальянского посольства.'],
            ['IT', 'student', 'language_certificate',    'required', 'CILS/CELI для итальяноязычных; IELTS/TOEFL для англоязычных.', null, 23, null],
            ['IT', 'student', 'bank_balance_certificate', 'required', 'Минимум ~6 000 EUR на год.', ['min_balance_usd' => 6500], 24, null],
            ['IT', 'student', 'travel_insurance',        'required', 'Медицинская страховка.', ['min_coverage_eur' => 30000], 25, null],
            ['IT', 'student', 'student_housing',         'required', 'Contratto di affitto или dichiarazione di ospitalità.', null, 26, null],
            ['IT', 'student', 'health_insurance_student', 'required', 'Медстраховка на период учёбы или запись в SSN.', null, 27, null],
            ['IT', 'student', 'tuition_payment',         'recommended', 'Квитанция об оплате.', null, 28, null],
            ['IT', 'student', 'birth_certificate',       'required', 'Свидетельство о рождении.', null, 29, null],
        ];
    }

    // ================================================================
    // ПОЛЬША — tourist
    // ================================================================
    private function polandTourist(): array
    {
        return [
            ['PL', 'tourist', 'itinerary',              'recommended', 'Маршрутный лист по дням.', null, 23, null],
            ['PL', 'tourist', 'cover_letter',            'recommended', 'Рекомендуется для первого Шенгена.', null, 24, null],
            ['PL', 'tourist', 'invitation_letter_private', 'recommended', 'Zaproszenie — приглашение, зарегистрированное в Urząd Wojewódzki.', null, 25, 'Польша: приглашение должно быть зарегистрировано в воеводском управлении.'],
        ];
    }

    // ================================================================
    // ПОЛЬША — business
    // ================================================================
    private function polandBusiness(): array
    {
        return [
            ['PL', 'business', 'business_invitation',   'required', 'Приглашение от польской компании.', null, 23, null],
            ['PL', 'business', 'company_registration',  'recommended', 'Свидетельство о регистрации компании.', null, 24, null],
            ['PL', 'business', 'employer_leave_approval', 'recommended', 'Разрешение от работодателя.', null, 25, null],
            ['PL', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 26, null],
        ];
    }

    // ================================================================
    // ЧЕХИЯ — tourist
    // ================================================================
    private function czechTourist(): array
    {
        return [
            ['CZ', 'tourist', 'itinerary',              'recommended', 'Маршрутный лист по дням.', null, 23, null],
            ['CZ', 'tourist', 'cover_letter',            'recommended', 'Рекомендуется.', null, 24, null],
            ['CZ', 'tourist', 'invitation_letter_private', 'recommended', 'Приглашение от частного лица с нотариальным заверением.', null, 25, null],
        ];
    }

    // ================================================================
    // ЧЕХИЯ — business
    // ================================================================
    private function czechBusiness(): array
    {
        return [
            ['CZ', 'business', 'business_invitation',   'required', 'Приглашение от чешской компании.', null, 23, null],
            ['CZ', 'business', 'company_registration',  'recommended', 'Свидетельство о регистрации компании.', null, 24, null],
            ['CZ', 'business', 'employer_leave_approval', 'recommended', 'Разрешение от работодателя.', null, 25, null],
            ['CZ', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 26, null],
        ];
    }

    // ================================================================
    // США — tourist (B1/B2)
    // ================================================================
    private function usaTourist(): array
    {
        return [
            ['US', 'tourist', 'ds160_form',             'confirmation_only', 'Заполнить на ceac.state.gov. Распечатать confirmation page.', null, 20, null],
            ['US', 'tourist', 'visa_fee_receipt',       'required', 'MRV fee $185 (с 2024). Оплатить через CGI Federal.', null, 21, null],
            ['US', 'tourist', 'hotel_booking',          'recommended', 'Не обязателен, но повышает шансы.', null, 22, null],
            ['US', 'tourist', 'air_tickets',            'recommended', 'Не покупать до получения визы. Бронь достаточно.', null, 23, null],
            ['US', 'tourist', 'cover_letter',           'recommended', 'Описание цели визита, маршрута, привязки к родине.', null, 24, null],
            ['US', 'tourist', 'property_documents',     'recommended', 'Подтверждение недвижимости — привязка к родине.', null, 25, null],
            ['US', 'tourist', 'proof_of_ties',          'recommended', 'Максимум доказательств привязки к родине: работа, бизнес, семья, имущество.', null, 26, 'США: консул оценивает ties to home country. Чем больше доказательств, тем лучше.'],
            ['US', 'tourist', 'invitation_letter_private', 'recommended', 'Если едет к родственникам/друзьям в США.', null, 27, null],
            ['US', 'tourist', 'itinerary',              'recommended', 'Подробный план поездки.', null, 28, null],
        ];
    }

    // ================================================================
    // США — business (B1)
    // ================================================================
    private function usaBusiness(): array
    {
        return [
            ['US', 'business', 'ds160_form',            'confirmation_only', 'Заполнить на ceac.state.gov.', null, 20, null],
            ['US', 'business', 'visa_fee_receipt',      'required', 'MRV fee $185.', null, 21, null],
            ['US', 'business', 'business_invitation',   'required', 'Письмо от американской компании с целью визита.', null, 22, null],
            ['US', 'business', 'conference_invitation',  'recommended', 'Если посещение конференции/выставки.', null, 23, null],
            ['US', 'business', 'company_registration',  'recommended', 'Свидетельство о регистрации компании.', null, 24, null],
            ['US', 'business', 'company_financial_statements', 'recommended', 'Финансовая отчётность компании.', null, 25, null],
            ['US', 'business', 'employer_leave_approval', 'recommended', 'Разрешение от работодателя на командировку.', null, 26, null],
            ['US', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 27, null],
            ['US', 'business', 'business_agenda',        'recommended', 'Программа деловых встреч.', null, 28, null],
            ['US', 'business', 'proof_of_ties',          'recommended', 'Привязки к родине.', null, 29, null],
        ];
    }

    // ================================================================
    // США — student (F-1) + exchange (J-1)
    // ================================================================
    private function usaStudent(): array
    {
        return [
            // F-1 Student
            ['US', 'student', 'ds160_form',             'confirmation_only', 'Заполнить на ceac.state.gov.', null, 20, null],
            ['US', 'student', 'i20_form',               'required', 'Форма I-20 от университета. SEVIS ID начинается с N.', null, 21, 'США: I-20 = ключевой документ для F-1. Без него виза невозможна.'],
            ['US', 'student', 'sevis_fee_receipt',      'required', 'Оплатить $350 на fmjfee.com за 3+ дня до интервью.', null, 22, null],
            ['US', 'student', 'visa_fee_receipt',       'required', 'MRV fee $185 для F-1.', null, 23, null],
            ['US', 'student', 'university_acceptance',  'required', 'Acceptance letter от университета.', null, 24, null],
            ['US', 'student', 'academic_transcript',    'required', 'Транскрипт оценок.', null, 25, null],
            ['US', 'student', 'diploma',                'required', 'Диплом с апостилем.', null, 26, null],
            ['US', 'student', 'language_certificate',   'required', 'TOEFL iBT 80+ / IELTS 6.5+.', null, 27, null],
            ['US', 'student', 'bank_balance_certificate', 'required', 'Минимум на 1 год обучения + проживание.', ['min_balance_usd' => 30000], 28, 'США: показать средства на весь первый год (tuition + living + insurance).'],
            ['US', 'student', 'study_plan',             'recommended', 'Важно объяснить career plan и ties to home country.', null, 29, null],
            ['US', 'student', 'scholarship_proof',      'recommended', 'Если есть стипендия — сильный плюс.', null, 30, null],
            ['US', 'student', 'cv_resume',              'recommended', 'CV / резюме.', null, 31, null],
            ['US', 'student', 'sponsor_affidavit',      'recommended', 'I-134 Affidavit of Support — если спонсор оплачивает.', null, 32, null],
            ['US', 'student', 'guarantor_income',       'recommended', 'Справка о доходах спонсора.', null, 33, null],
            ['US', 'student', 'guarantor_bank_statement', 'recommended', 'Выписка из банка спонсора.', null, 34, null],
            ['US', 'student', 'proof_of_ties',          'recommended', 'Привязки к родине.', null, 35, null],

            // J-1 Exchange
            ['US', 'exchange', 'ds160_form',            'confirmation_only', 'Заполнить на ceac.state.gov.', null, 20, null],
            ['US', 'exchange', 'ds2019_form',           'required', 'DS-2019 от спонсорской организации.', null, 21, 'SEVIS fee для J-1 = $220 (не $350).'],
            ['US', 'exchange', 'sevis_fee_receipt',     'required', 'SEVIS fee $220 для J-1.', null, 22, null],
            ['US', 'exchange', 'visa_fee_receipt',      'required', 'MRV fee $185.', null, 23, null],
            ['US', 'exchange', 'university_acceptance', 'required', 'Acceptance letter от принимающей организации.', null, 24, null],
            ['US', 'exchange', 'language_certificate',  'recommended', 'TOEFL/IELTS если требуется программой.', null, 25, null],
            ['US', 'exchange', 'bank_balance_certificate', 'required', 'Достаточные средства на период обмена.', null, 26, null],
            ['US', 'exchange', 'proof_of_ties',         'recommended', 'Привязки к родине.', null, 27, null],
        ];
    }

    // ================================================================
    // ВЕЛИКОБРИТАНИЯ — tourist
    // ================================================================
    private function ukTourist(): array
    {
        return [
            ['GB', 'tourist', 'vaf1a_form',             'confirmation_only', 'Заполнить онлайн на gov.uk. Распечатать confirmation.', null, 20, null],
            ['GB', 'tourist', 'hotel_booking',           'required', 'Подтверждение проживания.', null, 21, null],
            ['GB', 'tourist', 'air_tickets',             'required', 'Бронь билетов туда-обратно.', null, 22, null],
            ['GB', 'tourist', 'travel_insurance',        'recommended', 'Рекомендуется, не обязательна для UK.', null, 23, null],
            ['GB', 'tourist', 'cover_letter',            'recommended', 'Описание цели визита.', null, 24, null],
            ['GB', 'tourist', 'proof_of_ties',           'recommended', 'Привязки к родине.', null, 25, null],
            ['GB', 'tourist', 'tb_test',                 'recommended', 'TB тест для виз свыше 6 мес. Для Standard Visitor (до 6 мес) НЕ требуется. Клиника: Tashkent International Clinic.', null, 26, 'UK: TB test обязателен только для виз > 6 мес. Standard Visitor до 6 мес — не требуется.'],
            ['GB', 'tourist', 'itinerary',               'recommended', 'План поездки по дням.', null, 27, null],
        ];
    }

    // ================================================================
    // ВЕЛИКОБРИТАНИЯ — business
    // ================================================================
    private function ukBusiness(): array
    {
        return [
            ['GB', 'business', 'vaf1a_form',            'confirmation_only', 'Заполнить онлайн на gov.uk.', null, 20, null],
            ['GB', 'business', 'business_invitation',    'required', 'Letter of invitation от UK компании.', null, 21, null],
            ['GB', 'business', 'hotel_booking',          'required', 'Подтверждение проживания.', null, 22, null],
            ['GB', 'business', 'air_tickets',            'required', 'Бронь билетов.', null, 23, null],
            ['GB', 'business', 'conference_invitation',  'recommended', 'Если посещение конференции.', null, 24, null],
            ['GB', 'business', 'company_registration',   'recommended', 'Свидетельство о регистрации компании.', null, 25, null],
            ['GB', 'business', 'tb_test',                'required', 'TB тест обязателен для граждан Узбекистана.', null, 26, null],
            ['GB', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 27, null],
            ['GB', 'business', 'business_agenda',        'recommended', 'Программа встреч.', null, 28, null],
        ];
    }

    // ================================================================
    // ВЕЛИКОБРИТАНИЯ — student (Tier 4 / Student)
    // ================================================================
    private function ukStudent(): array
    {
        return [
            ['GB', 'student', 'cas_letter',              'required', 'CAS от университета — ключевой документ. 14-значный номер.', null, 20, 'UK: CAS обязателен. Спонсор должен иметь Student sponsor licence.'],
            ['GB', 'student', 'university_acceptance',   'required', 'Unconditional offer от университета.', null, 21, null],
            ['GB', 'student', 'academic_transcript',     'required', 'Транскрипт оценок.', null, 22, null],
            ['GB', 'student', 'diploma',                 'required', 'Диплом с переводом.', null, 23, null],
            ['GB', 'student', 'language_certificate',    'required', 'IELTS for UKVI / Trinity ISE. НЕ обычный IELTS Academic.', null, 24, 'UK: принимается только IELTS for UKVI или Trinity ISE.'],
            ['GB', 'student', 'bank_balance_certificate', 'required', 'Средства на курс + 9 мес. проживания. Лондон: £1 483/мес, вне Лондона: £1 136/мес (с 02.01.2025).', ['min_balance_usd' => 18000], 25, 'UK maintenance 2025: London £1 483/мес, outside London £1 136/мес.'],
            ['GB', 'student', 'bank_statement',          'required', 'Средства на счёте 28+ дней подряд без перерыва.', ['period_months' => 1], 26, 'UK: деньги должны быть на счёте минимум 28 дней.'],
            ['GB', 'student', 'tb_test',                 'required', 'TB тест обязателен.', null, 27, null],
            ['GB', 'student', 'health_insurance_student', 'required', 'IHS (Immigration Health Surcharge) — оплатить онлайн при подаче.', null, 28, 'UK: IHS оплачивается онлайн при подаче заявки.'],
            ['GB', 'student', 'study_plan',              'recommended', 'Мотивационное письмо.', null, 29, null],
            ['GB', 'student', 'cv_resume',               'recommended', 'CV / резюме.', null, 30, null],
            ['GB', 'student', 'birth_certificate',       'required', 'Свидетельство о рождении.', null, 31, null],
            ['GB', 'student', 'parental_consent',        'recommended', 'Обязательно для заявителей до 18 лет.', null, 32, null],
        ];
    }

    // ================================================================
    // КАНАДА — tourist
    // ================================================================
    private function canadaTourist(): array
    {
        return [
            ['CA', 'tourist', 'hotel_booking',           'recommended', 'Подтверждение проживания.', null, 20, null],
            ['CA', 'tourist', 'air_tickets',             'recommended', 'Бронь билетов.', null, 21, null],
            ['CA', 'tourist', 'travel_insurance',        'recommended', 'Рекомендуется.', null, 22, null],
            ['CA', 'tourist', 'itinerary',               'recommended', 'План поездки.', null, 23, null],
            ['CA', 'tourist', 'cover_letter',            'recommended', 'Объяснить цель визита и привязки к родине.', null, 24, null],
            ['CA', 'tourist', 'proof_of_ties',           'recommended', 'Привязки к родине: работа, бизнес, семья, имущество.', null, 25, null],
            ['CA', 'tourist', 'invitation_letter_private', 'recommended', 'Если едет по приглашению — письмо от приглашающего.', null, 26, null],
        ];
    }

    // ================================================================
    // КАНАДА — business
    // ================================================================
    private function canadaBusiness(): array
    {
        return [
            ['CA', 'business', 'business_invitation',    'required', 'Letter of invitation от канадской компании.', null, 20, null],
            ['CA', 'business', 'air_tickets',            'recommended', 'Бронь билетов.', null, 21, null],
            ['CA', 'business', 'company_registration',   'recommended', 'Свидетельство о регистрации компании.', null, 22, null],
            ['CA', 'business', 'conference_invitation',  'recommended', 'Если посещение конференции.', null, 23, null],
            ['CA', 'business', 'cover_letter',           'recommended', 'Описание цели командировки.', null, 24, null],
            ['CA', 'business', 'proof_of_ties',          'recommended', 'Привязки к родине.', null, 25, null],
        ];
    }

    // ================================================================
    // КАНАДА — student (Study Permit)
    // ================================================================
    private function canadaStudent(): array
    {
        return [
            ['CA', 'student', 'university_acceptance',   'required', 'Letter of Acceptance (LOA) от DLI.', null, 20, 'Канада: вуз должен быть Designated Learning Institution (DLI).'],
            ['CA', 'student', 'pal_tal',                 'required', 'Provincial Attestation Letter (PAL). Обязателен с 2024 (кроме магистратуры/PhD).', null, 21, 'Канада: без PAL study permit будет отклонён. Исключения: магистратура, PhD, K-12.'],
            ['CA', 'student', 'academic_transcript',     'required', 'Транскрипт оценок.', null, 22, null],
            ['CA', 'student', 'diploma',                 'required', 'Диплом с переводом.', null, 23, null],
            ['CA', 'student', 'language_certificate',    'required', 'IELTS Academic 6.0+ / TEF для французских программ.', null, 24, null],
            ['CA', 'student', 'bank_balance_certificate', 'required', 'Tuition 1-го года + CAD 22 895 на проживание (с 01.09.2025).', ['min_balance_usd' => 27000], 25, 'Канада: tuition + CAD 22 895 living costs (с 01.09.2025).'],
            ['CA', 'student', 'bank_statement',          'required', 'Выписка за 4+ месяцев.', ['period_months' => 4], 26, null],
            ['CA', 'student', 'study_plan',              'required', 'Study plan ОБЯЗАТЕЛЕН для Канады. Объяснить: почему Канада, почему этот курс, планы после.', null, 27, 'Канада: study plan — один из ключевых документов.'],
            ['CA', 'student', 'tuition_payment',         'recommended', 'Оплата 1-го семестра повышает шансы.', null, 28, null],
            ['CA', 'student', 'scholarship_proof',       'recommended', 'Если есть стипендия.', null, 29, null],
            ['CA', 'student', 'guarantor_letter',        'recommended', 'Если есть спонсор.', null, 30, null],
            ['CA', 'student', 'guarantor_income',        'recommended', 'Справка о доходах спонсора.', null, 31, null],
            ['CA', 'student', 'guarantor_bank_statement', 'recommended', 'Выписка из банка спонсора за 4 мес.', ['period_months' => 4], 32, null],
            ['CA', 'student', 'police_clearance',        'required', 'PCC из каждой страны, где жил 6+ мес. после 18 лет.', null, 33, null],
            ['CA', 'student', 'medical_certificate',     'required', 'Upfront medical exam в аккредитованной клинике.', null, 34, null],
            ['CA', 'student', 'cv_resume',               'recommended', 'CV / резюме.', null, 35, null],
            ['CA', 'student', 'proof_of_ties',           'recommended', 'Привязки к родине.', null, 36, null],
            ['CA', 'student', 'caq_certificate',         'recommended', 'ОБЯЗАТЕЛЕН для Квебека (вместо PAL). Подаётся на сайте MIDI.', null, 37, 'Канада/Квебек: CAQ вместо PAL.'],
            ['CA', 'student', 'gic_certificate',         'recommended', 'GIC для Student Direct Stream (SDS) — ускоренная обработка (~CAD 20 635).', null, 38, 'SDS: GIC + IELTS 6.0 overall = ускоренная обработка.'],
        ];
    }

    // ================================================================
    // АВСТРАЛИЯ — tourist (600)
    // ================================================================
    private function australiaTourist(): array
    {
        return [
            ['AU', 'tourist', 'hotel_booking',           'recommended', 'Подтверждение проживания.', null, 20, null],
            ['AU', 'tourist', 'air_tickets',             'recommended', 'Бронь билетов.', null, 21, null],
            ['AU', 'tourist', 'travel_insurance',        'recommended', 'Рекомендуется.', null, 22, null],
            ['AU', 'tourist', 'cover_letter',            'recommended', 'Описание цели визита.', null, 23, null],
            ['AU', 'tourist', 'proof_of_ties',           'recommended', 'Привязки к родине.', null, 24, null],
            ['AU', 'tourist', 'invitation_letter_private', 'recommended', 'Если едет по приглашению.', null, 25, null],
            ['AU', 'tourist', 'itinerary',               'recommended', 'План поездки.', null, 26, null],
        ];
    }

    // ================================================================
    // АВСТРАЛИЯ — student (500)
    // ================================================================
    private function australiaStudent(): array
    {
        return [
            ['AU', 'student', 'coe_confirmation',        'required', 'Confirmation of Enrolment (CoE) — без него подача невозможна.', null, 20, 'Австралия: CoE выдаётся после оплаты депозита. Провайдер должен быть в CRICOS.'],
            ['AU', 'student', 'genuine_student_evidence', 'required', 'Genuine Student (GS) requirement — ключевой документ. Объяснить: почему этот курс, почему Австралия.', null, 21, 'Австралия: GS оценивает current circumstances, ties, why this course, benefit.'],
            ['AU', 'student', 'academic_transcript',     'required', 'Транскрипт оценок.', null, 22, null],
            ['AU', 'student', 'diploma',                 'required', 'Диплом с переводом.', null, 23, null],
            ['AU', 'student', 'language_certificate',    'required', 'IELTS Academic 5.5-6.5 / PTE Academic.', null, 24, null],
            ['AU', 'student', 'bank_balance_certificate', 'required', 'Tuition + AUD 24 505/год на проживание + AUD 2 000 на перелёт.', ['min_balance_usd' => 25000], 25, null],
            ['AU', 'student', 'tuition_payment',         'required', 'Депозит / оплата 1-го семестра обязательна для CoE.', null, 26, null],
            ['AU', 'student', 'health_insurance_student', 'required', 'OSHC (Overseas Student Health Cover) — обязательна на весь период учёбы.', null, 27, 'Австралия: OSHC обязательна. Провайдеры: Allianz, Medibank, BUPA, NIB.'],
            ['AU', 'student', 'police_clearance',        'required', 'Справка о несудимости.', null, 28, null],
            ['AU', 'student', 'medical_certificate',     'required', 'Через Bupa Medical Visa Services.', null, 29, null],
            ['AU', 'student', 'study_plan',              'required', 'Входит в Genuine Student requirement.', null, 30, null],
            ['AU', 'student', 'cv_resume',               'recommended', 'CV / резюме.', null, 31, null],
            ['AU', 'student', 'proof_of_ties',           'recommended', 'Genuine Student: доказательства привязки к родине.', null, 32, null],
            ['AU', 'student', 'under18_welfare',         'recommended', 'CAAW — обязательно для студентов до 18 лет.', null, 33, null],
        ];
    }

    // ================================================================
    // ОАЭ — tourist
    // ================================================================
    private function uaeTourist(): array
    {
        return [
            ['AE', 'tourist', 'hotel_booking',           'required', 'Подтверждение бронирования отеля в ОАЭ.', null, 20, null],
            ['AE', 'tourist', 'air_tickets',             'required', 'Обратный билет обязателен.', null, 21, null],
            ['AE', 'tourist', 'travel_insurance',        'recommended', 'Рекомендуется, но не обязательна для ОАЭ.', null, 22, null],
            ['AE', 'tourist', 'cover_letter',            'recommended', 'Описание цели визита.', null, 23, null],
        ];
    }

    // ================================================================
    // ОАЭ — business
    // ================================================================
    private function uaeBusiness(): array
    {
        return [
            ['AE', 'business', 'business_invitation',   'required', 'Приглашение от компании в ОАЭ + копия trade license компании.', null, 20, 'ОАЭ: нужна копия trade license приглашающей компании.'],
            ['AE', 'business', 'hotel_booking',          'required', 'Подтверждение проживания.', null, 21, null],
            ['AE', 'business', 'air_tickets',            'required', 'Обратный билет.', null, 22, null],
            ['AE', 'business', 'company_registration',   'recommended', 'Свидетельство о регистрации компании заявителя.', null, 23, null],
            ['AE', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 24, null],
        ];
    }

    // ================================================================
    // ЮЖНАЯ КОРЕЯ — tourist (C-3)
    // ================================================================
    private function koreaTourist(): array
    {
        return [
            ['KR', 'tourist', 'hotel_booking',           'required', 'Подтверждение проживания в Корее.', null, 20, null],
            ['KR', 'tourist', 'air_tickets',             'required', 'Обратный билет обязателен.', null, 21, null],
            ['KR', 'tourist', 'travel_insurance',        'required', 'Медицинская страховка на период пребывания.', null, 22, null],
            ['KR', 'tourist', 'itinerary',               'required', 'Подробный план поездки по дням.', null, 23, 'Корея: подробный маршрут обязателен.'],
            ['KR', 'tourist', 'cover_letter',            'recommended', 'Описание цели визита.', null, 24, null],
            ['KR', 'tourist', 'invitation_letter_private', 'recommended', 'Если едет по приглашению корейцев.', null, 25, null],
        ];
    }

    // ================================================================
    // ЮЖНАЯ КОРЕЯ — business
    // ================================================================
    private function koreaBusiness(): array
    {
        return [
            ['KR', 'business', 'business_invitation',   'required', 'Приглашение от корейской компании.', null, 20, null],
            ['KR', 'business', 'hotel_booking',          'required', 'Подтверждение проживания.', null, 21, null],
            ['KR', 'business', 'air_tickets',            'required', 'Обратный билет.', null, 22, null],
            ['KR', 'business', 'travel_insurance',       'required', 'Медицинская страховка.', null, 23, null],
            ['KR', 'business', 'company_registration',   'recommended', 'Свидетельство о регистрации компании.', null, 24, null],
            ['KR', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 25, null],
        ];
    }

    // ================================================================
    // ЯПОНИЯ — tourist
    // ================================================================
    private function japanTourist(): array
    {
        return [
            ['JP', 'tourist', 'hotel_booking',           'required', 'Подтверждение проживания.', null, 20, null],
            ['JP', 'tourist', 'air_tickets',             'required', 'Обратный билет.', null, 21, null],
            ['JP', 'tourist', 'itinerary',               'required', 'Schedule of Stay — подробный маршрут по дням: даты, города, активности. Обязателен для MOFA.', null, 22, 'Япония: Schedule of Stay обязателен. Формат MOFA.'],
            ['JP', 'tourist', 'cover_letter',            'recommended', 'Описание цели визита.', null, 23, null],
            ['JP', 'tourist', 'invitation_letter_private', 'recommended', 'Пригласительное письмо от принимающей стороны в Японии.', null, 24, 'Япония (СНГ): часто требуется гарант (hoshosha) или приглашающая сторона.'],
            ['JP', 'tourist', 'guarantor_letter',        'recommended', 'Письмо-гарантия (hoshosha) от лица в Японии.', null, 25, null],
            ['JP', 'tourist', 'guarantor_income',        'recommended', 'Подтверждение финансовой состоятельности гаранта.', null, 26, null],
            ['JP', 'tourist', 'travel_insurance',        'recommended', 'Рекомендуется.', null, 27, null],
        ];
    }

    // ================================================================
    // ЯПОНИЯ — business
    // ================================================================
    private function japanBusiness(): array
    {
        return [
            ['JP', 'business', 'business_invitation',   'required', 'Приглашение от японской компании.', null, 20, null],
            ['JP', 'business', 'hotel_booking',          'required', 'Подтверждение проживания.', null, 21, null],
            ['JP', 'business', 'air_tickets',            'required', 'Обратный билет.', null, 22, null],
            ['JP', 'business', 'itinerary',              'required', 'Программа визита по дням.', null, 23, null],
            ['JP', 'business', 'company_registration',   'recommended', 'Свидетельство о регистрации компании.', null, 24, null],
            ['JP', 'business', 'company_cover_letter',   'recommended', 'Письмо от командирующей компании.', null, 25, null],
        ];
    }

    // ================================================================
    // ЮЖНАЯ КОРЕЯ — student (D-2)
    // ================================================================
    private function koreaStudent(): array
    {
        return [
            ['KR', 'student', 'university_acceptance',   'required', 'Certificate of Admission (CoA) от университета.', null, 20, null],
            ['KR', 'student', 'academic_transcript',     'required', 'Транскрипт оценок.', null, 21, null],
            ['KR', 'student', 'diploma',                 'required', 'Диплом с апостилем или подтверждением от посольства.', null, 22, null],
            ['KR', 'student', 'language_certificate',    'required', 'TOPIK 3+ для корейскоязычных; IELTS/TOEFL для англоязычных.', null, 23, null],
            ['KR', 'student', 'bank_balance_certificate', 'required', 'Минимум 20 000 000 KRW (включая стоимость обучения).', ['min_balance_usd' => 15000], 24, 'Корея: минимум 20M KRW на счёте.'],
            ['KR', 'student', 'tuition_payment',         'required', 'Подтверждение оплаты обучения.', null, 25, null],
            ['KR', 'student', 'tb_test',                 'required', 'Тест на туберкулёз — обязателен для граждан Узбекистана.', null, 26, 'Корея: Узбекистан в списке 19 стран, TB тест обязателен.'],
            ['KR', 'student', 'travel_insurance',        'required', 'Медицинская страховка.', null, 27, null],
            ['KR', 'student', 'scholarship_proof',       'recommended', 'Стипендия от GKS/KGSP — сильный плюс.', null, 28, null],
            ['KR', 'student', 'study_plan',              'recommended', 'Мотивационное письмо.', null, 29, null],
        ];
    }

    // ================================================================
    // ЯПОНИЯ — student (College of Technology / University)
    // ================================================================
    private function japanStudent(): array
    {
        return [
            ['JP', 'student', 'coe_confirmation',        'required', 'Certificate of Eligibility (COE) — оформляется принимающим вузом в Японии.', null, 20, 'Япония: COE = ключевой документ. Без него виза невозможна.'],
            ['JP', 'student', 'university_acceptance',   'required', 'Acceptance letter от университета.', null, 21, null],
            ['JP', 'student', 'academic_transcript',     'required', 'Транскрипт оценок.', null, 22, null],
            ['JP', 'student', 'diploma',                 'required', 'Диплом с переводом.', null, 23, null],
            ['JP', 'student', 'language_certificate',    'required', 'JLPT N2+ для японоязычных; IELTS/TOEFL для англоязычных.', null, 24, null],
            ['JP', 'student', 'bank_balance_certificate', 'required', 'Достаточные средства на обучение и проживание.', ['min_balance_usd' => 15000], 25, null],
            ['JP', 'student', 'study_plan',              'recommended', 'Мотивационное письмо.', null, 26, null],
            ['JP', 'student', 'guarantor_letter',        'recommended', 'Письмо от гаранта в Японии.', null, 27, null],
        ];
    }

    // ================================================================
    // ТУРЦИЯ — tourist
    // ================================================================
    private function turkeyTourist(): array
    {
        return [
            // Турция: БЕЗВИЗОВЫЙ РЕЖИМ для граждан Узбекистана (до 90 дней в 180-дневном периоде)
            // Документы ниже — рекомендации для пограничного контроля, не визовые требования
            ['TR', 'tourist', 'hotel_booking',           'recommended', 'Безвизовый режим. Может потребоваться на границе.', null, 20, 'Турция: безвизовый для UZ до 90 дней. Документы — для пограничного контроля.'],
            ['TR', 'tourist', 'air_tickets',             'recommended', 'Безвизовый режим. Обратный билет рекомендуется.', null, 21, null],
            ['TR', 'tourist', 'travel_insurance',        'recommended', 'Рекомендуется.', null, 22, null],
        ];
    }
}
