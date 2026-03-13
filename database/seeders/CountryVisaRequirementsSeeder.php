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
        // ai_country_notes: страно-специфичные AI-инструкции

        $requirements = array_merge(
            $this->universal(),
            $this->universalFamily(),
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
            $this->koreaTourist(),
            $this->japanTourist(),
            $this->turkeyTourist(),
            $this->schengenFamilyMinor(),
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
            ['*', '*', 'foreign_passport',         'required',           null, null, 1, null],
            ['*', '*', 'internal_passport',        'required',           null, null, 2, null],
            ['*', '*', 'photo_3x4',                'confirmation_only',  null, null, 3, null],
            ['*', '*', 'application_form',         'confirmation_only',  null, null, 4, null],
            ['*', '*', 'income_certificate',       'required',           null, null, 5, null],
            ['*', '*', 'bank_balance_certificate', 'required',           null, null, 6, null],
            ['*', '*', 'bank_statement',           'required',           null, null, 7, null],
            ['*', '*', 'marriage_certificate',     'recommended',        'Если в браке — обязательно.', null, 8, null],
            ['*', '*', 'child_birth_certificate',  'recommended',        'Если есть дети — обязательно.', null, 9, null],
            ['*', '*', 'property_certificate',     'recommended',        null, null, 10, null],
            ['*', '*', 'car_registration',         'recommended',        null, null, 11, null],
            ['*', '*', 'criminal_record',          'recommended',        null, null, 12, null],
            ['*', '*', 'old_passports',            'recommended',        'Все старые паспорта с визами — сильный плюс.', null, 13, null],
            ['*', '*', 'previous_visa_copies',     'recommended',        null, null, 14, null],
            ['*', '*', 'work_contract',            'recommended',        'Трудовой договор — дополнительное подтверждение занятости.', null, 15, null],
            ['*', '*', 'work_book',                'recommended',        'Полная история занятости.', null, 16, null],
            ['*', '*', 'business_ownership',       'recommended',        'Для владельцев бизнеса.', null, 17, null],
            ['*', '*', 'family_composition',       'recommended',        null, null, 18, null],
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
            ['*', '*', 'foreign_passport',         'required',           'Паспорт члена семьи', null, 1, null, 'family_all'],
            ['*', '*', 'photo_3x4',                'confirmation_only',  'Фото члена семьи', null, 2, null, 'family_all'],

            // Для СУПРУГА — свидетельство о браке
            ['*', '*', 'marriage_certificate',     'required',           'Свидетельство о заключении брака. Нотариальный перевод.', null, 3, null, 'family_spouse'],

            // Для ДЕТЕЙ — свидетельство о рождении
            ['*', '*', 'child_birth_certificate',  'required',           'Свидетельство о рождении ребёнка. Нотариальный перевод.', null, 3, null, 'family_child'],

            // Для НЕСОВЕРШЕННОЛЕТНИХ — специфичные документы (birth_certificate уже в family_child)
            ['*', '*', 'parental_consent',         'required',           'Нотариальное согласие на выезд от обоих родителей (если едет с одним). Обязательно для несовершеннолетних.', null, 4, null, 'family_minor'],

            // Для РОДИТЕЛЕЙ — документ подтверждающий родство
            ['*', '*', 'child_birth_certificate',  'required',           'Свидетельство о рождении заявителя (подтверждение родства с родителем).', null, 3, null, 'family_parent'],
        ];
    }

    // ================================================================
    // ШЕНГЕН — доп. документы для несовершеннолетних
    // ================================================================
    private function schengenFamilyMinor(): array
    {
        $schengenCountries = ['FR', 'DE', 'ES', 'IT'];
        $visaTypes = ['tourist', 'business'];
        $items = [];

        foreach ($schengenCountries as $cc) {
            foreach ($visaTypes as $vt) {
                // Страховка для ребёнка — обязательна в Шенгене
                $items[] = [$cc, $vt, 'travel_insurance', 'required', 'Страховка для несовершеннолетнего. Минимум 30 000 EUR, зона Шенген.', ['min_coverage_eur' => 30000], 6, null, 'family_minor'];
            }
        }

        // UK — TB тест для семьи тоже нужен
        $items[] = ['GB', 'tourist', 'tb_test', 'required', 'TB тест для члена семьи. Обязателен для граждан Узбекистана.', null, 6, null, 'family_all'];
        $items[] = ['GB', 'business', 'tb_test', 'required', 'TB тест для члена семьи.', null, 6, null, 'family_all'];
        $items[] = ['GB', 'student', 'tb_test', 'required', 'TB тест для члена семьи.', null, 6, null, 'family_all'];

        return $items;
    }

    // ================================================================
    // ФРАНЦИЯ
    // ================================================================
    private function franceTourist(): array
    {
        return [
            ['FR', 'tourist', 'hotel_booking',    'required', null, null, 20, null],
            ['FR', 'tourist', 'air_tickets',      'required', null, null, 21, null],
            ['FR', 'tourist', 'travel_insurance', 'required', 'Минимум 30 000 EUR, зона Шенген.', ['min_coverage_eur' => 30000], 22, 'Проверить: зона покрытия = Schengen, репатриация включена.'],
            ['FR', 'tourist', 'itinerary',        'recommended', null, null, 23, null],
            ['FR', 'tourist', 'cover_letter',     'recommended', 'Рекомендуется для первого Шенгена.', null, 24, null],
            ['FR', 'tourist', 'invitation_letter_private', 'recommended', 'Attestation d\'accueil — заверяется мэрией.', null, 25, 'Для Франции: attestation d\'accueil обязательно заверяется mairie.'],
            ['FR', 'tourist', 'guarantor_letter',  'recommended', null, null, 26, null],
            ['FR', 'tourist', 'guarantor_income',  'recommended', null, null, 27, null],
            ['FR', 'tourist', 'guarantor_bank_statement', 'recommended', null, ['period_months' => 3], 28, null],
        ];
    }

    private function franceBusiness(): array
    {
        return [
            ['FR', 'business', 'business_invitation',  'required', 'Invitation/request letter с описанием цели, длительности и места встречи.', null, 20, 'Для Франции business: нужно точное описание активности + кто покрывает расходы.'],
            ['FR', 'business', 'travel_insurance',      'required', 'Минимум 30 000 EUR.', ['min_coverage_eur' => 30000], 21, null],
            ['FR', 'business', 'air_tickets',           'required', null, null, 22, null],
            ['FR', 'business', 'hotel_booking',         'required', null, null, 23, null],
            ['FR', 'business', 'company_registration',  'recommended', null, null, 24, null],
            ['FR', 'business', 'conference_invitation', 'recommended', 'Admission cards на выставки и конференции.', null, 25, null],
            ['FR', 'business', 'company_financial_statements', 'recommended', null, null, 26, null],
            ['FR', 'business', 'financial_guarantee_org', 'recommended', 'Engagement de la société d\'accueil.', null, 27, null],
            ['FR', 'business', 'employer_leave_approval', 'recommended', null, null, 28, null],
            ['FR', 'business', 'noc_letter',            'recommended', null, null, 29, null],
            ['FR', 'business', 'company_cover_letter',  'recommended', 'Письмо от командирующей компании.', null, 30, null],
            ['FR', 'business', 'business_agenda',       'recommended', 'Программа встреч — повышает шансы.', null, 31, null],
            ['FR', 'business', 'event_ticket',          'recommended', 'Билет на конференцию/выставку.', null, 32, null],
            ['FR', 'business', 'commercial_relationship_proof', 'recommended', 'Договоры, инвойсы — история отношений.', null, 33, null],
            ['FR', 'business', 'host_expense_guarantee', 'recommended', 'Engagement de prise en charge.', null, 34, null],
            ['FR', 'business', 'mission_order',         'recommended', null, null, 35, null],
            ['FR', 'tourist', 'attestation_accueil',    'recommended', 'Если проживание у частного лица.', null, 30, null],
        ];
    }

    private function franceStudent(): array
    {
        return [
            ['FR', 'student', 'university_acceptance', 'required', 'Attestation de pré-inscription / lettre d\'admission.', null, 20, 'Для Франции: нужен сертификат Campus France + attestation de pré-inscription.'],
            ['FR', 'student', 'campus_france_attestation', 'required', 'ОБЯЗАТЕЛЬНО — без attestation Campus France консульство не примет досье.', null, 21, 'СТОП если attestation отрицательный.'],
            ['FR', 'student', 'academic_transcript',   'required', null, null, 22, null],
            ['FR', 'student', 'diploma',               'required', 'Нотариальный перевод + апостиль.', null, 23, null],
            ['FR', 'student', 'language_certificate',  'required', 'DELF B2 / TCF для франкоязычных программ; IELTS/TOEFL для англоязычных.', null, 24, 'Минимум DELF B2 для программ на французском, IELTS 6.0 для англоязычных.'],
            ['FR', 'student', 'study_plan',            'required', 'Мотивационное письмо — почему Франция, этот вуз, карьерный план.', null, 25, null],
            ['FR', 'student', 'travel_insurance',      'required', null, ['min_coverage_eur' => 30000], 26, null],
            ['FR', 'student', 'bank_balance_certificate', 'required', 'Минимум 615 EUR/мес на год (~7 380 EUR).', ['min_balance_usd' => 8000], 27, 'Франция: 615 EUR/мес минимум (ресурсы du gouvernement français).'],
            ['FR', 'student', 'tuition_payment',       'recommended', null, null, 28, null],
            ['FR', 'student', 'student_housing',       'required', 'Attestation d\'hébergement или contrat de bail.', null, 29, null],
            ['FR', 'student', 'scholarship_proof',     'recommended', 'Стипендия Campus France / Erasmus — сильный плюс.', null, 30, null],
            ['FR', 'student', 'birth_certificate',     'required', 'Нотариальный перевод.', null, 31, null],
            ['FR', 'student', 'cv_resume',             'recommended', null, null, 32, null],
            ['FR', 'student', 'health_insurance_student', 'required', 'CVEC + LMDE/SMERRA или приватная.', null, 33, null],
            ['FR', 'student', 'reference_letters',     'recommended', null, null, 34, null],
        ];
    }

    // ================================================================
    // ГЕРМАНИЯ
    // ================================================================
    private function germanyTourist(): array
    {
        return [
            ['DE', 'tourist', 'hotel_booking',    'required', null, null, 20, null],
            ['DE', 'tourist', 'air_tickets',      'required', null, null, 21, null],
            ['DE', 'tourist', 'travel_insurance', 'required', 'Минимум 30 000 EUR, Шенген.', ['min_coverage_eur' => 30000], 22, null],
            ['DE', 'tourist', 'itinerary',        'recommended', null, null, 23, null],
            ['DE', 'tourist', 'cover_letter',     'recommended', null, null, 24, null],
        ];
    }

    private function germanyBusiness(): array
    {
        return [
            ['DE', 'business', 'business_invitation',  'required', 'Письмо от принимающей компании + Verpflichtungserklärung.', null, 20, 'Германия: может потребоваться Verpflichtungserklärung (гарантийное обязательство).'],
            ['DE', 'business', 'travel_insurance',     'required', 'Минимум 30 000 EUR.', ['min_coverage_eur' => 30000], 21, null],
            ['DE', 'business', 'air_tickets',          'required', null, null, 22, null],
            ['DE', 'business', 'company_registration', 'recommended', null, null, 23, null],
            ['DE', 'business', 'conference_invitation', 'recommended', null, null, 24, null],
            ['DE', 'business', 'employer_leave_approval', 'recommended', null, null, 25, null],
            ['DE', 'business', 'company_cover_letter',  'recommended', null, null, 26, null],
            ['DE', 'business', 'business_agenda',       'recommended', null, null, 27, null],
            ['DE', 'business', 'verpflichtungserklaerung', 'recommended', 'Формальное обязательство — Ausländerbehörde.', null, 28, 'Германия: Verpflichtungserklärung заменяет финансовые гарантии заявителя.'],
            ['DE', 'tourist', 'verpflichtungserklaerung', 'recommended', 'Если проживание у частного лица.', null, 25, null],
        ];
    }

    private function germanyStudent(): array
    {
        return [
            ['DE', 'student', 'university_acceptance', 'required', 'Zulassungsbescheid — решение о зачислении.', null, 20, 'Германия: Zulassungsbescheid от вуза обязателен.'],
            ['DE', 'student', 'aps_certificate',       'required', 'APS-Zertifikat обязателен для граждан Узбекистана.', null, 21, 'СТОП если APS отрицательный. Срок ожидания 4-8 недель.'],
            ['DE', 'student', 'blocked_account',       'required', 'Sperrkonto: минимум 11 208 EUR (~934 EUR/мес).', ['min_balance_usd' => 12000], 22, 'Германия: Sperrkonto через Expatrio/Fintiba/Deutsche Bank. Сумма пересматривается ежегодно.'],
            ['DE', 'student', 'academic_transcript',   'required', null, null, 23, null],
            ['DE', 'student', 'diploma',               'required', 'Апостиль + признание через anabin.', null, 24, 'Германия: проверить признание диплома на anabin.kmk.org.'],
            ['DE', 'student', 'language_certificate',  'required', 'TestDaF 4x4 или DSH-2 для немецкоязычных; IELTS 6.0+ для англоязычных.', null, 25, null],
            ['DE', 'student', 'study_plan',            'recommended', null, null, 26, null],
            ['DE', 'student', 'travel_insurance',      'required', 'Немецкая страховка: TK, AOK, MAWISTA.', null, 27, 'Германия: обязательна немецкая медстраховка (не путевая).'],
            ['DE', 'student', 'health_insurance_student', 'required', null, null, 28, null],
            ['DE', 'student', 'tuition_payment',       'recommended', 'Семестровый взнос (Semesterbeitrag).', null, 29, null],
            ['DE', 'student', 'student_housing',       'recommended', 'Wohnheimzusage или Mietvertrag.', null, 30, null],
            ['DE', 'student', 'cv_resume',             'recommended', 'Формат Europass.', null, 31, null],
            ['DE', 'student', 'birth_certificate',     'required', null, null, 32, null],
        ];
    }

    // ================================================================
    // ИСПАНИЯ
    // ================================================================
    private function spainTourist(): array
    {
        return [
            ['ES', 'tourist', 'hotel_booking',    'required', null, null, 20, null],
            ['ES', 'tourist', 'air_tickets',      'required', 'Допустима стыковка.', null, 21, null],
            ['ES', 'tourist', 'travel_insurance', 'required', 'Минимум 30 000 EUR, Шенген.', ['min_coverage_eur' => 30000], 22, null],
            ['ES', 'tourist', 'itinerary',        'recommended', null, null, 23, null],
            ['ES', 'tourist', 'cover_letter',     'recommended', 'Рекомендуется для первого Шенгена.', null, 24, null],
            ['ES', 'tourist', 'guarantor_letter',  'recommended', null, null, 25, null],
            ['ES', 'tourist', 'guarantor_income',  'recommended', null, null, 26, null],
            ['ES', 'tourist', 'guarantor_bank_statement', 'recommended', null, ['period_months' => 3], 27, null],
        ];
    }

    private function spainBusiness(): array
    {
        return [
            ['ES', 'business', 'business_invitation',  'required', null, null, 20, null],
            ['ES', 'business', 'travel_insurance',     'required', null, ['min_coverage_eur' => 30000], 21, null],
            ['ES', 'business', 'air_tickets',          'required', null, null, 22, null],
            ['ES', 'business', 'hotel_booking',        'required', null, null, 23, null],
            ['ES', 'business', 'company_registration', 'recommended', null, null, 24, null],
        ];
    }

    private function spainStudent(): array
    {
        return [
            ['ES', 'student', 'university_acceptance', 'required', 'Carta de admisión.', null, 20, null],
            ['ES', 'student', 'academic_transcript',   'required', null, null, 21, null],
            ['ES', 'student', 'diploma',               'required', 'Апостиль обязателен.', null, 22, null],
            ['ES', 'student', 'language_certificate',  'required', 'DELE для испаноязычных; IELTS для англоязычных.', null, 23, null],
            ['ES', 'student', 'bank_balance_certificate', 'required', 'Минимум ~600 EUR/мес.', ['min_balance_usd' => 7500], 24, null],
            ['ES', 'student', 'travel_insurance',      'required', null, ['min_coverage_eur' => 30000], 25, null],
            ['ES', 'student', 'student_housing',       'required', null, null, 26, null],
            ['ES', 'student', 'health_insurance_student', 'required', null, null, 27, null],
            ['ES', 'student', 'tuition_payment',       'recommended', null, null, 28, null],
            ['ES', 'student', 'criminal_record',       'required', 'Для студенческой визы — обязателен.', null, 29, null],
            ['ES', 'student', 'medical_certificate',   'required', null, null, 30, null],
        ];
    }

    // ================================================================
    // ИТАЛИЯ
    // ================================================================
    private function italyTourist(): array
    {
        return [
            ['IT', 'tourist', 'hotel_booking',    'required', null, null, 20, null],
            ['IT', 'tourist', 'air_tickets',      'required', null, null, 21, null],
            ['IT', 'tourist', 'travel_insurance', 'required', 'Минимум 30 000 EUR, Шенген.', ['min_coverage_eur' => 30000], 22, null],
            ['IT', 'tourist', 'itinerary',        'recommended', null, null, 23, null],
        ];
    }

    private function italyBusiness(): array
    {
        return [
            ['IT', 'business', 'business_invitation',  'required', null, null, 20, null],
            ['IT', 'business', 'travel_insurance',     'required', null, ['min_coverage_eur' => 30000], 21, null],
            ['IT', 'business', 'air_tickets',          'required', null, null, 22, null],
            ['IT', 'business', 'hotel_booking',        'required', null, null, 23, null],
            ['IT', 'business', 'company_registration', 'recommended', null, null, 24, null],
        ];
    }

    // ================================================================
    // США
    // ================================================================
    private function usaTourist(): array
    {
        return [
            ['US', 'tourist', 'ds160_form',         'confirmation_only', 'Заполнить на ceac.state.gov.', null, 20, null],
            ['US', 'tourist', 'visa_fee_receipt',   'required', 'MRV fee $160.', null, 21, null],
            ['US', 'tourist', 'property_documents', 'recommended', null, null, 22, null],
            ['US', 'tourist', 'hotel_booking',      'recommended', 'Не обязателен но повышает шансы.', null, 23, null],
            ['US', 'tourist', 'air_tickets',        'recommended', null, null, 24, null],
            ['US', 'tourist', 'cover_letter',       'recommended', null, null, 25, null],
            ['US', 'tourist', 'business_ownership', 'recommended', 'Сильный фактор привязки.', null, 26, null],
            ['US', 'tourist', 'family_composition', 'recommended', null, null, 27, null],
            ['US', 'tourist', 'proof_of_ties',      'recommended', 'Максимум доказательств привязки к родине.', null, 28, 'США: консул оценивает ties to home country. Чем больше доказательств, тем лучше.'],
        ];
    }

    private function usaBusiness(): array
    {
        return [
            ['US', 'business', 'ds160_form',           'confirmation_only', null, null, 20, null],
            ['US', 'business', 'visa_fee_receipt',     'required', 'MRV fee $160.', null, 21, null],
            ['US', 'business', 'business_invitation',  'required', 'Письмо от американской компании с целью визита.', null, 22, null],
            ['US', 'business', 'conference_invitation', 'recommended', null, null, 23, null],
            ['US', 'business', 'company_registration', 'recommended', null, null, 24, null],
            ['US', 'business', 'company_financial_statements', 'recommended', null, null, 25, null],
            ['US', 'business', 'employer_leave_approval', 'recommended', null, null, 26, null],
            ['US', 'business', 'company_cover_letter',  'recommended', null, null, 27, null],
            ['US', 'business', 'business_agenda',       'recommended', null, null, 28, null],
            ['US', 'business', 'event_ticket',          'recommended', null, null, 29, null],
            ['US', 'business', 'commercial_relationship_proof', 'recommended', null, null, 30, null],
        ];
    }

    private function usaStudent(): array
    {
        return [
            ['US', 'student', 'ds160_form',            'confirmation_only', null, null, 20, null],
            ['US', 'student', 'i20_form',              'required', 'Форма I-20 от университета. SEVIS ID начинается с N.', null, 21, 'США: I-20 = ключевой документ для F-1. Без него виза невозможна.'],
            ['US', 'student', 'sevis_fee_receipt',     'required', 'Оплатить $350 на fmjfee.com за 3+ дня до интервью.', null, 22, null],
            ['US', 'student', 'visa_fee_receipt',      'required', 'MRV fee $185 для F-1.', null, 23, null],
            ['US', 'student', 'university_acceptance', 'required', null, null, 24, null],
            ['US', 'student', 'academic_transcript',   'required', null, null, 25, null],
            ['US', 'student', 'diploma',               'required', null, null, 26, null],
            ['US', 'student', 'language_certificate',  'required', 'TOEFL iBT 80+ / IELTS 6.5+.', null, 27, null],
            ['US', 'student', 'bank_balance_certificate', 'required', 'Минимум на 1 год обучения + проживание.', ['min_balance_usd' => 30000], 28, 'США: показать средства на весь первый год (tuition + living + insurance).'],
            ['US', 'student', 'study_plan',            'recommended', 'Важно объяснить career plan и ties to home country.', null, 29, null],
            ['US', 'student', 'scholarship_proof',     'recommended', null, null, 30, null],
            ['US', 'student', 'cv_resume',             'recommended', null, null, 31, null],
            ['US', 'student', 'guarantor_letter',      'recommended', 'Если спонсор — I-134 Affidavit of Support.', null, 32, null],
            ['US', 'student', 'guarantor_income',      'recommended', null, null, 33, null],
            ['US', 'student', 'guarantor_bank_statement', 'recommended', null, null, 34, null],
            ['US', 'student', 'proof_of_ties',         'recommended', null, null, 35, null],
            ['US', 'student', 'sponsor_affidavit',     'recommended', 'I-134 Affidavit of Support.', null, 36, null],
            ['US', 'student', 'education_loan_approval', 'recommended', null, null, 37, null],

            // США — exchange (J-1)
            ['US', 'exchange', 'ds160_form',           'confirmation_only', null, null, 20, null],
            ['US', 'exchange', 'ds2019_form',          'required', 'DS-2019 от спонсорской организации.', null, 21, 'SEVIS fee для J-1 = $220 (не $350).'],
            ['US', 'exchange', 'sevis_fee_receipt',    'required', 'SEVIS fee $220 для J-1.', null, 22, null],
            ['US', 'exchange', 'visa_fee_receipt',     'required', 'MRV fee $160.', null, 23, null],
            ['US', 'exchange', 'university_acceptance', 'required', null, null, 24, null],
            ['US', 'exchange', 'language_certificate', 'recommended', null, null, 25, null],
            ['US', 'exchange', 'bank_balance_certificate', 'required', null, null, 26, null],
            ['US', 'exchange', 'proof_of_ties',        'recommended', null, null, 27, null],
        ];
    }

    // ================================================================
    // ВЕЛИКОБРИТАНИЯ
    // ================================================================
    private function ukTourist(): array
    {
        return [
            ['GB', 'tourist', 'vaf1a_form',       'confirmation_only', 'Заполнить на gov.uk.', null, 20, null],
            ['GB', 'tourist', 'hotel_booking',     'required', null, null, 21, null],
            ['GB', 'tourist', 'air_tickets',       'required', null, null, 22, null],
            ['GB', 'tourist', 'travel_insurance',  'recommended', 'Рекомендуется, не обязательна.', null, 23, null],
            ['GB', 'tourist', 'cover_letter',      'recommended', null, null, 24, null],
            ['GB', 'tourist', 'proof_of_ties',     'recommended', null, null, 25, null],
            ['GB', 'tourist', 'tb_test',           'required', 'Обязателен для граждан Узбекистана (виза > 6 мес.).', null, 26, 'UK: TB test обязателен для Узбекистана. Только аккредитованные клиники.'],
        ];
    }

    private function ukBusiness(): array
    {
        return [
            ['GB', 'business', 'vaf1a_form',          'confirmation_only', null, null, 20, null],
            ['GB', 'business', 'business_invitation',  'required', 'Letter of invitation от UK компании.', null, 21, null],
            ['GB', 'business', 'hotel_booking',        'required', null, null, 22, null],
            ['GB', 'business', 'air_tickets',          'required', null, null, 23, null],
            ['GB', 'business', 'conference_invitation', 'recommended', null, null, 24, null],
            ['GB', 'business', 'company_registration', 'recommended', null, null, 25, null],
            ['GB', 'business', 'tb_test',              'required', null, null, 26, null],
            ['GB', 'business', 'company_cover_letter',  'recommended', null, null, 27, null],
            ['GB', 'business', 'business_agenda',       'recommended', null, null, 28, null],
            ['GB', 'business', 'event_ticket',          'recommended', null, null, 29, null],
        ];
    }

    private function ukStudent(): array
    {
        return [
            ['GB', 'student', 'cas_letter',            'required', 'CAS от университета — ключевой документ.', null, 20, 'UK: CAS (14-значный номер) обязателен. Спонсор должен иметь лицензию Tier 4.'],
            ['GB', 'student', 'university_acceptance',  'required', null, null, 21, null],
            ['GB', 'student', 'academic_transcript',    'required', null, null, 22, null],
            ['GB', 'student', 'diploma',                'required', null, null, 23, null],
            ['GB', 'student', 'language_certificate',   'required', 'IELTS for UKVI / Trinity ISE.', null, 24, 'UK: принимается только IELTS for UKVI или Trinity ISE (не обычный IELTS Academic).'],
            ['GB', 'student', 'bank_balance_certificate', 'required', 'Средства на курс + 9 мес. проживания. Лондон: £1334/мес, вне Лондона: £1023/мес.', ['min_balance_usd' => 15000], 25, null],
            ['GB', 'student', 'bank_statement',         'required', 'Средства на счёте 28+ дней подряд.', ['period_months' => 1], 26, 'UK: деньги должны быть на счёте минимум 28 дней без перерыва.'],
            ['GB', 'student', 'tb_test',                'required', null, null, 27, null],
            ['GB', 'student', 'health_insurance_student', 'required', 'IHS (Immigration Health Surcharge) — оплата онлайн.', null, 28, 'UK: IHS оплачивается онлайн при подаче заявки, не нужен отдельный полис.'],
            ['GB', 'student', 'study_plan',             'recommended', null, null, 29, null],
            ['GB', 'student', 'cv_resume',              'recommended', null, null, 30, null],
            ['GB', 'student', 'guarantor_letter',       'recommended', null, null, 31, null],
            ['GB', 'student', 'birth_certificate',      'required', null, null, 32, null],
            ['GB', 'student', 'parental_consent',       'recommended', 'Обязательно для заявителей до 18 лет.', null, 33, null],
        ];
    }

    // ================================================================
    // КАНАДА
    // ================================================================
    private function canadaTourist(): array
    {
        return [
            ['CA', 'tourist', 'hotel_booking',     'recommended', null, null, 20, null],
            ['CA', 'tourist', 'air_tickets',       'recommended', null, null, 21, null],
            ['CA', 'tourist', 'travel_insurance',  'recommended', null, null, 22, null],
            ['CA', 'tourist', 'itinerary',         'recommended', null, null, 23, null],
            ['CA', 'tourist', 'cover_letter',      'recommended', 'Объяснить цель визита и привязки к родине.', null, 24, null],
            ['CA', 'tourist', 'proof_of_ties',     'recommended', null, null, 25, null],
            ['CA', 'tourist', 'invitation_letter_private', 'recommended', null, null, 26, null],
        ];
    }

    private function canadaBusiness(): array
    {
        return [
            ['CA', 'business', 'business_invitation',  'required', 'Letter of invitation от канадской компании.', null, 20, null],
            ['CA', 'business', 'air_tickets',          'recommended', null, null, 21, null],
            ['CA', 'business', 'company_registration', 'recommended', null, null, 22, null],
            ['CA', 'business', 'conference_invitation', 'recommended', null, null, 23, null],
            ['CA', 'business', 'cover_letter',         'recommended', null, null, 24, null],
        ];
    }

    private function canadaStudent(): array
    {
        return [
            ['CA', 'student', 'university_acceptance',  'required', 'Letter of Acceptance (LOA) от DLI.', null, 20, 'Канада: вуз должен быть Designated Learning Institution (DLI). Проверить на сайте IRCC.'],
            ['CA', 'student', 'academic_transcript',    'required', null, null, 21, null],
            ['CA', 'student', 'diploma',                'required', null, null, 22, null],
            ['CA', 'student', 'language_certificate',   'required', 'IELTS Academic 6.0+ / TEF для французских программ.', null, 23, null],
            ['CA', 'student', 'bank_balance_certificate', 'required', 'Tuition 1-го года + CAD 20 635 на проживание (или CAD 25 690 для Квебека).', ['min_balance_usd' => 25000], 24, 'Канада: показать tuition + living costs на 1 год. GIC (~CAD 20 635) ускоряет обработку через SDS.'],
            ['CA', 'student', 'bank_statement',         'required', 'Выписка за 4+ месяцев.', ['period_months' => 4], 25, null],
            ['CA', 'student', 'study_plan',             'required', 'Study plan ОБЯЗАТЕЛЕН для Канады.', null, 26, 'Канада: study plan — один из ключевых документов. Объяснить: почему Канада, почему этот курс, планы после.'],
            ['CA', 'student', 'tuition_payment',        'recommended', 'Оплата 1-го семестра повышает шансы.', null, 27, null],
            ['CA', 'student', 'scholarship_proof',      'recommended', null, null, 28, null],
            ['CA', 'student', 'guarantor_letter',       'recommended', null, null, 29, null],
            ['CA', 'student', 'guarantor_income',       'recommended', null, null, 30, null],
            ['CA', 'student', 'guarantor_bank_statement', 'recommended', null, ['period_months' => 4], 31, null],
            ['CA', 'student', 'police_clearance',       'required', 'PCC из каждой страны, где жил 6+ мес. после 18 лет.', null, 32, null],
            ['CA', 'student', 'medical_certificate',    'required', 'Upfront medical exam в аккредитованной клинике.', null, 33, null],
            ['CA', 'student', 'cv_resume',              'recommended', null, null, 34, null],
            ['CA', 'student', 'proof_of_ties',          'recommended', null, null, 35, null],
            ['CA', 'student', 'pal_tal',                'required', 'PAL/TAL обязателен с 2024 (кроме магистратуры/PhD).', null, 36, 'Канада: без PAL/TAL study permit будет отклонён. Исключения: магистратура, PhD, K-12.'],
            ['CA', 'student', 'caq_certificate',        'recommended', 'ОБЯЗАТЕЛЕН для Квебека (вместо PAL).', null, 37, 'Канада/Квебек: CAQ вместо PAL. Подаётся на сайте MIDI.'],
            ['CA', 'student', 'gic_certificate',        'recommended', 'GIC для Student Direct Stream (SDS) — ускоренная обработка.', null, 38, 'SDS: GIC ~CAD 20 635 + IELTS 6.0 overall = ускоренная обработка.'],
            ['CA', 'student', 'education_loan_approval', 'recommended', null, null, 39, null],
            ['CA', 'student', 'sponsor_affidavit',      'recommended', null, null, 40, null],
        ];
    }

    // ================================================================
    // АВСТРАЛИЯ
    // ================================================================
    private function australiaTourist(): array
    {
        return [
            ['AU', 'tourist', 'hotel_booking',     'recommended', null, null, 20, null],
            ['AU', 'tourist', 'air_tickets',       'recommended', null, null, 21, null],
            ['AU', 'tourist', 'travel_insurance',  'recommended', null, null, 22, null],
            ['AU', 'tourist', 'cover_letter',      'recommended', null, null, 23, null],
            ['AU', 'tourist', 'proof_of_ties',     'recommended', null, null, 24, null],
        ];
    }

    private function australiaStudent(): array
    {
        return [
            ['AU', 'student', 'university_acceptance',  'required', 'Confirmation of Enrolment (CoE).', null, 20, 'Австралия: CoE выдаётся после оплаты депозита. Без CoE подача невозможна.'],
            ['AU', 'student', 'academic_transcript',    'required', null, null, 21, null],
            ['AU', 'student', 'diploma',                'required', null, null, 22, null],
            ['AU', 'student', 'language_certificate',   'required', 'IELTS Academic 5.5-6.5 / PTE Academic / Cambridge.', null, 23, null],
            ['AU', 'student', 'bank_balance_certificate', 'required', 'Tuition + AUD 24 505/год на проживание + AUD 2 000 на перелёт.', ['min_balance_usd' => 25000], 24, null],
            ['AU', 'student', 'study_plan',             'required', 'Genuine Student (GS) requirement — ключевой документ.', null, 25, 'Австралия: GS requirement — система оценивает current circumstances, ties, why this course, benefit. Подкреплять доказательствами.'],
            ['AU', 'student', 'tuition_payment',        'required', 'Депозит / оплата 1-го семестра обязательна для CoE.', null, 26, null],
            ['AU', 'student', 'health_insurance_student', 'required', 'OSHC (Overseas Student Health Cover) — обязательна.', null, 27, 'Австралия: OSHC обязательна на весь период учёбы. Провайдеры: Allianz, Medibank, BUPA, NIB.'],
            ['AU', 'student', 'police_clearance',       'required', null, null, 28, null],
            ['AU', 'student', 'medical_certificate',    'required', 'Через Bupa Medical Visa Services.', null, 29, null],
            ['AU', 'student', 'cv_resume',              'recommended', null, null, 30, null],
            ['AU', 'student', 'guarantor_letter',       'recommended', null, null, 31, null],
            ['AU', 'student', 'guarantor_income',       'recommended', null, null, 32, null],
            ['AU', 'student', 'proof_of_ties',          'recommended', 'Genuine Student: доказательства привязки к родине.', null, 33, null],
            ['AU', 'student', 'parental_consent',       'recommended', 'Для заявителей до 18 — welfare arrangement обязателен.', null, 34, null],
            ['AU', 'student', 'coe_confirmation',       'required', 'CoE — без него подача невозможна.', null, 35, 'Австралия: CoE выдаётся после оплаты депозита. Провайдер должен быть в CRICOS.'],
            ['AU', 'student', 'genuine_student_evidence', 'required', 'GS requirement — подкреплять доказательствами.', null, 36, 'Австралия: GS оценивает current circumstances, ties, why course/provider, benefit, prior evidence.'],
            ['AU', 'student', 'under18_welfare',        'recommended', 'CAAW — обязательно для студентов до 18.', null, 37, null],
            ['AU', 'student', 'education_loan_approval', 'recommended', null, null, 38, null],
            ['AU', 'student', 'sponsor_affidavit',      'recommended', null, null, 39, null],
        ];
    }

    // ================================================================
    // ОАЭ
    // ================================================================
    private function uaeTourist(): array
    {
        return [
            ['AE', 'tourist', 'hotel_booking', 'required', 'Подтверждение проживания в ОАЭ.', null, 20, null],
            ['AE', 'tourist', 'air_tickets',   'required', null, null, 21, null],
        ];
    }

    // ================================================================
    // КОРЕЯ
    // ================================================================
    private function koreaTourist(): array
    {
        return [
            ['KR', 'tourist', 'hotel_booking',          'required', null, null, 20, null],
            ['KR', 'tourist', 'air_tickets',            'required', null, null, 21, null],
            ['KR', 'tourist', 'travel_insurance',       'required', null, null, 22, null],
            ['KR', 'tourist', 'employment_certificate', 'required', 'Обязателен для корейской визы.', null, 23, null],
            ['KR', 'tourist', 'noc_letter',             'recommended', null, null, 24, null],
        ];
    }

    // ================================================================
    // ЯПОНИЯ
    // ================================================================
    private function japanTourist(): array
    {
        return [
            ['JP', 'tourist', 'hotel_booking',          'required', null, null, 20, null],
            ['JP', 'tourist', 'air_tickets',            'required', null, null, 21, null],
            ['JP', 'tourist', 'itinerary',              'required', 'Подробный маршрут по дням обязателен.', null, 22, 'Япония: подробный маршрут обязателен. По дням: город, отель, активности.'],
            ['JP', 'tourist', 'employment_certificate', 'required', null, null, 23, null],
            ['JP', 'tourist', 'noc_letter',             'recommended', null, null, 24, null],
            ['JP', 'tourist', 'invitation_letter_private', 'recommended', null, null, 25, null],
        ];
    }

    // ================================================================
    // ТУРЦИЯ
    // ================================================================
    private function turkeyTourist(): array
    {
        return [
            ['TR', 'tourist', 'hotel_booking',    'required', null, null, 20, null],
            ['TR', 'tourist', 'air_tickets',      'required', null, null, 21, null],
            ['TR', 'tourist', 'travel_insurance', 'recommended', null, null, 22, null],
        ];
    }
}
