<?php

namespace App\Modules\Scoring\Services;

use App\Modules\PublicPortal\Models\PublicUser;
use App\Modules\Scoring\Models\ClientProfile;

/**
 * Адаптер, который конвертирует различные модели в единый массив
 * для UnifiedScoringEngine.
 */
class ScoringDataAdapter
{
    /**
     * Конвертировать PublicUser в массив данных для скоринга.
     */
    public static function fromPublicUser(PublicUser $user): array
    {
        return [
            // Финансы
            'employment_type'      => $user->employment_type,
            'monthly_income_usd'   => $user->monthly_income_usd,
            'income_type'          => null, // нет у публичных пользователей
            'bank_history_months'  => 0,
            'bank_balance_stable'  => false,
            'has_fixed_deposit'    => false,
            'has_investments'      => false,

            // Привязанность
            'marital_status'       => $user->marital_status,
            'spouse_employed'      => false, // нет у публичных пользователей
            'has_children'         => $user->has_children,
            'children_count'       => $user->children_count ?? 0,
            'children_staying_home'=> $user->has_children, // предполагаем дома
            'has_property'         => $user->has_property,
            'has_car'              => $user->has_car,
            'has_business'         => $user->has_business ?? false,
            'employed_years'       => $user->employed_years ?? 0,

            // Визовая история
            'has_us_visa'          => $user->has_us_visa,
            'has_schengen_visa'    => $user->has_schengen_visa,
            'has_uk_visa'          => false, // нет у публичных пользователей
            'visas_obtained_count' => $user->visas_obtained_count ?? 0,
            'refusals_count'       => $user->refusals_count ?? 0,
            'had_overstay'         => $user->had_overstay,
            'had_deportation'      => $user->had_deportation ?? false,
            'last_refusal_year'    => $user->last_refusal_year,

            // Профиль
            'education_level'      => $user->education_level,
            'dob'                  => $user->dob,
            'has_criminal_record'  => false,
        ];
    }

    /**
     * Конвертировать ClientProfile (CRM) в массив данных для скоринга.
     */
    public static function fromClientProfile(ClientProfile $p): array
    {
        return [
            // Финансы
            'employment_type'      => $p->employment_type,
            'monthly_income'       => $p->monthly_income,
            'income_type'          => $p->income_type,
            'bank_history_months'  => $p->bank_history_months ?? 0,
            'bank_balance_stable'  => $p->bank_balance_stable ?? false,
            'has_fixed_deposit'    => $p->has_fixed_deposit ?? false,
            'has_investments'      => $p->has_investments ?? false,

            // Привязанность
            'marital_status'       => $p->marital_status,
            'spouse_employed'      => $p->spouse_employed ?? false,
            'has_children'         => ($p->children_count ?? 0) > 0,
            'children_count'       => $p->children_count ?? 0,
            'children_staying_home'=> $p->children_staying_home ?? (($p->children_count ?? 0) > 0),
            'has_real_estate'      => $p->has_real_estate ?? false,
            'has_car'              => $p->has_car ?? false,
            'has_business'         => $p->has_business ?? false,
            'years_at_current_job' => $p->years_at_current_job ?? 0,
            'total_work_experience'=> $p->total_work_experience ?? 0,

            // Визовая история
            'has_us_visa'          => $p->has_us_visa ?? false,
            'has_schengen_visa'    => $p->has_schengen_visa ?? false,
            'has_uk_visa'          => $p->has_uk_visa ?? false,
            'previous_refusals'    => $p->previous_refusals ?? 0,
            'has_overstay'         => $p->has_overstay ?? false,
            'has_criminal_record'  => $p->has_criminal_record ?? false,

            // Профиль
            'education_level'      => $p->education_level,
            'age'                  => $p->age ?? 0,
        ];
    }
}
