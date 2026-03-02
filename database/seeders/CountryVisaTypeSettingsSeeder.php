<?php

namespace Database\Seeders;

use App\Modules\Owner\Models\CountryVisaTypeSetting;
use Illuminate\Database\Seeder;

class CountryVisaTypeSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Германия
            ['country_code' => 'DE', 'visa_type' => 'tourist',  'preparation_days' => 7,  'appointment_wait_days' => 14, 'processing_days_min' => 7,  'processing_days_max' => 15, 'processing_days_avg' => 10, 'buffer_days' => 7,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 25, 'avg_refusal_rate' => 12.5],
            ['country_code' => 'DE', 'visa_type' => 'business', 'preparation_days' => 10, 'appointment_wait_days' => 14, 'processing_days_min' => 5,  'processing_days_max' => 15, 'processing_days_avg' => 10, 'buffer_days' => 7,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 25, 'avg_refusal_rate' => 8.0],
            ['country_code' => 'DE', 'visa_type' => 'student',  'preparation_days' => 30, 'appointment_wait_days' => 21, 'processing_days_min' => 14, 'processing_days_max' => 60, 'processing_days_avg' => 30, 'buffer_days' => 14, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => true,  'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 75,  'service_fee_usd' => 25, 'avg_refusal_rate' => 15.0],
            ['country_code' => 'DE', 'visa_type' => 'work',     'preparation_days' => 30, 'appointment_wait_days' => 28, 'processing_days_min' => 30, 'processing_days_max' => 90, 'processing_days_avg' => 60, 'buffer_days' => 14, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => true,  'appointment_pattern' => 'fixed_schedule', 'consular_fee_usd' => 75,  'service_fee_usd' => 25, 'avg_refusal_rate' => 10.0],

            // Франция
            ['country_code' => 'FR', 'visa_type' => 'tourist',  'preparation_days' => 7,  'appointment_wait_days' => 10, 'processing_days_min' => 5,  'processing_days_max' => 15, 'processing_days_avg' => 8,  'buffer_days' => 5,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 30, 'avg_refusal_rate' => 10.0],
            ['country_code' => 'FR', 'visa_type' => 'business', 'preparation_days' => 10, 'appointment_wait_days' => 10, 'processing_days_min' => 5,  'processing_days_max' => 15, 'processing_days_avg' => 8,  'buffer_days' => 5,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 30, 'avg_refusal_rate' => 6.0],
            ['country_code' => 'FR', 'visa_type' => 'student',  'preparation_days' => 30, 'appointment_wait_days' => 14, 'processing_days_min' => 14, 'processing_days_max' => 45, 'processing_days_avg' => 25, 'buffer_days' => 14, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => true,  'appointment_pattern' => 'fixed_schedule', 'consular_fee_usd' => 99,  'service_fee_usd' => 30, 'avg_refusal_rate' => 12.0],

            // Италия
            ['country_code' => 'IT', 'visa_type' => 'tourist',  'preparation_days' => 7,  'appointment_wait_days' => 14, 'processing_days_min' => 5,  'processing_days_max' => 15, 'processing_days_avg' => 10, 'buffer_days' => 5,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 20, 'avg_refusal_rate' => 9.0],
            ['country_code' => 'IT', 'visa_type' => 'business', 'preparation_days' => 10, 'appointment_wait_days' => 14, 'processing_days_min' => 5,  'processing_days_max' => 15, 'processing_days_avg' => 10, 'buffer_days' => 5,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 20, 'avg_refusal_rate' => 5.0],
            ['country_code' => 'IT', 'visa_type' => 'student',  'preparation_days' => 21, 'appointment_wait_days' => 21, 'processing_days_min' => 14, 'processing_days_max' => 45, 'processing_days_avg' => 25, 'buffer_days' => 14, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 50,  'service_fee_usd' => 20, 'avg_refusal_rate' => 11.0],

            // Испания
            ['country_code' => 'ES', 'visa_type' => 'tourist',  'preparation_days' => 7,  'appointment_wait_days' => 14, 'processing_days_min' => 5,  'processing_days_max' => 15, 'processing_days_avg' => 10, 'buffer_days' => 5,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 22, 'avg_refusal_rate' => 8.0],
            ['country_code' => 'ES', 'visa_type' => 'business', 'preparation_days' => 10, 'appointment_wait_days' => 14, 'processing_days_min' => 5,  'processing_days_max' => 15, 'processing_days_avg' => 10, 'buffer_days' => 5,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 22, 'avg_refusal_rate' => 5.0],
            ['country_code' => 'ES', 'visa_type' => 'student',  'preparation_days' => 30, 'appointment_wait_days' => 21, 'processing_days_min' => 30, 'processing_days_max' => 60, 'processing_days_avg' => 40, 'buffer_days' => 14, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 22, 'avg_refusal_rate' => 10.0],

            // Польша
            ['country_code' => 'PL', 'visa_type' => 'tourist',  'preparation_days' => 5,  'appointment_wait_days' => 10, 'processing_days_min' => 5,  'processing_days_max' => 15, 'processing_days_avg' => 8,  'buffer_days' => 5,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 20, 'avg_refusal_rate' => 7.0],
            ['country_code' => 'PL', 'visa_type' => 'work',     'preparation_days' => 14, 'appointment_wait_days' => 14, 'processing_days_min' => 14, 'processing_days_max' => 60, 'processing_days_avg' => 30, 'buffer_days' => 10, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 80,  'service_fee_usd' => 20, 'avg_refusal_rate' => 8.0],

            // Чехия
            ['country_code' => 'CZ', 'visa_type' => 'tourist',  'preparation_days' => 5,  'appointment_wait_days' => 14, 'processing_days_min' => 7,  'processing_days_max' => 15, 'processing_days_avg' => 10, 'buffer_days' => 5,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'fixed_schedule', 'consular_fee_usd' => 80,  'service_fee_usd' => 0,  'avg_refusal_rate' => 11.0],
            ['country_code' => 'CZ', 'visa_type' => 'student',  'preparation_days' => 21, 'appointment_wait_days' => 21, 'processing_days_min' => 30, 'processing_days_max' => 60, 'processing_days_avg' => 45, 'buffer_days' => 14, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => true,  'appointment_pattern' => 'fixed_schedule', 'consular_fee_usd' => 100, 'service_fee_usd' => 0,  'avg_refusal_rate' => 13.0],

            // Великобритания
            ['country_code' => 'GB', 'visa_type' => 'tourist',  'preparation_days' => 10, 'appointment_wait_days' => 7,  'processing_days_min' => 15, 'processing_days_max' => 21, 'processing_days_avg' => 15, 'buffer_days' => 7,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 115, 'service_fee_usd' => 55, 'avg_refusal_rate' => 15.0],
            ['country_code' => 'GB', 'visa_type' => 'student',  'preparation_days' => 21, 'appointment_wait_days' => 7,  'processing_days_min' => 15, 'processing_days_max' => 21, 'processing_days_avg' => 15, 'buffer_days' => 14, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 490, 'service_fee_usd' => 55, 'avg_refusal_rate' => 12.0],
            ['country_code' => 'GB', 'visa_type' => 'work',     'preparation_days' => 30, 'appointment_wait_days' => 7,  'processing_days_min' => 15, 'processing_days_max' => 56, 'processing_days_avg' => 21, 'buffer_days' => 14, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 719, 'service_fee_usd' => 55, 'avg_refusal_rate' => 10.0],

            // США
            ['country_code' => 'US', 'visa_type' => 'tourist',  'preparation_days' => 14, 'appointment_wait_days' => 60, 'processing_days_min' => 3,  'processing_days_max' => 14, 'processing_days_avg' => 5,  'buffer_days' => 7,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => true,  'appointment_pattern' => 'fixed_schedule', 'consular_fee_usd' => 185, 'service_fee_usd' => 0,  'avg_refusal_rate' => 25.0],
            ['country_code' => 'US', 'visa_type' => 'business', 'preparation_days' => 14, 'appointment_wait_days' => 60, 'processing_days_min' => 3,  'processing_days_max' => 14, 'processing_days_avg' => 5,  'buffer_days' => 7,  'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => true,  'appointment_pattern' => 'fixed_schedule', 'consular_fee_usd' => 185, 'service_fee_usd' => 0,  'avg_refusal_rate' => 20.0],
            ['country_code' => 'US', 'visa_type' => 'student',  'preparation_days' => 30, 'appointment_wait_days' => 60, 'processing_days_min' => 3,  'processing_days_max' => 30, 'processing_days_avg' => 7,  'buffer_days' => 14, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => true,  'appointment_pattern' => 'fixed_schedule', 'consular_fee_usd' => 185, 'service_fee_usd' => 0,  'avg_refusal_rate' => 18.0],

            // Канада
            ['country_code' => 'CA', 'visa_type' => 'tourist',  'preparation_days' => 14, 'appointment_wait_days' => 14, 'processing_days_min' => 14, 'processing_days_max' => 45, 'processing_days_avg' => 28, 'buffer_days' => 10, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 100, 'service_fee_usd' => 35, 'avg_refusal_rate' => 20.0],
            ['country_code' => 'CA', 'visa_type' => 'student',  'preparation_days' => 30, 'appointment_wait_days' => 14, 'processing_days_min' => 30, 'processing_days_max' => 90, 'processing_days_avg' => 56, 'buffer_days' => 14, 'biometrics_required' => true,  'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'daily_slots',    'consular_fee_usd' => 150, 'service_fee_usd' => 35, 'avg_refusal_rate' => 30.0],

            // Южная Корея
            ['country_code' => 'KR', 'visa_type' => 'tourist',  'preparation_days' => 5,  'appointment_wait_days' => 3,  'processing_days_min' => 5,  'processing_days_max' => 10, 'processing_days_avg' => 7,  'buffer_days' => 5,  'biometrics_required' => false, 'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'no_appointment', 'consular_fee_usd' => 40,  'service_fee_usd' => 0,  'avg_refusal_rate' => 5.0],
            ['country_code' => 'KR', 'visa_type' => 'work',     'preparation_days' => 14, 'appointment_wait_days' => 7,  'processing_days_min' => 14, 'processing_days_max' => 30, 'processing_days_avg' => 21, 'buffer_days' => 7,  'biometrics_required' => false, 'personal_visit_required' => true,  'interview_required' => false, 'appointment_pattern' => 'no_appointment', 'consular_fee_usd' => 60,  'service_fee_usd' => 0,  'avg_refusal_rate' => 8.0],

            // ОАЭ
            ['country_code' => 'AE', 'visa_type' => 'tourist',  'preparation_days' => 3,  'appointment_wait_days' => 0,  'processing_days_min' => 2,  'processing_days_max' => 5,  'processing_days_avg' => 3,  'buffer_days' => 3,  'biometrics_required' => false, 'personal_visit_required' => false, 'interview_required' => false, 'appointment_pattern' => 'no_appointment', 'consular_fee_usd' => 90,  'service_fee_usd' => 0,  'avg_refusal_rate' => 3.0],
            ['country_code' => 'AE', 'visa_type' => 'business', 'preparation_days' => 5,  'appointment_wait_days' => 0,  'processing_days_min' => 3,  'processing_days_max' => 7,  'processing_days_avg' => 5,  'buffer_days' => 3,  'biometrics_required' => false, 'personal_visit_required' => false, 'interview_required' => false, 'appointment_pattern' => 'no_appointment', 'consular_fee_usd' => 90,  'service_fee_usd' => 0,  'avg_refusal_rate' => 5.0],
            ['country_code' => 'AE', 'visa_type' => 'transit',  'preparation_days' => 2,  'appointment_wait_days' => 0,  'processing_days_min' => 1,  'processing_days_max' => 3,  'processing_days_avg' => 2,  'buffer_days' => 2,  'biometrics_required' => false, 'personal_visit_required' => false, 'interview_required' => false, 'appointment_pattern' => 'no_appointment', 'consular_fee_usd' => 0,   'service_fee_usd' => 0,  'avg_refusal_rate' => 1.0],
        ];

        foreach ($settings as $s) {
            CountryVisaTypeSetting::updateOrCreate(
                ['country_code' => $s['country_code'], 'visa_type' => $s['visa_type']],
                array_merge($s, ['is_active' => true, 'parallel_docs_allowed' => true])
            );
        }

        $this->command->info('Country visa type settings seeded: ' . count($settings) . ' entries.');
    }
}
