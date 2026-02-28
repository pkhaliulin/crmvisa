<?php

namespace App\Modules\Scoring\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientProfile extends Model
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'client_id',
        // F
        'monthly_income', 'income_type', 'bank_balance', 'bank_history_months',
        'bank_balance_stable', 'has_fixed_deposit', 'fixed_deposit_amount',
        'has_investments', 'investments_amount',
        // E
        'employment_type', 'employer_name', 'position', 'position_level',
        'years_at_current_job', 'total_work_experience', 'has_employment_gaps',
        // FM
        'marital_status', 'spouse_employed', 'children_count',
        'children_staying_home', 'dependents_count',
        // A
        'has_real_estate', 'has_car', 'has_business',
        // T
        'has_schengen_visa', 'has_us_visa', 'has_uk_visa',
        'previous_refusals', 'has_overstay',
        // P
        'education_level', 'has_criminal_record', 'age',
        // G
        'travel_purpose', 'has_return_ticket', 'has_hotel_booking',
        'has_invitation_letter', 'trip_duration_days', 'sponsor_covers_expenses',
    ];

    protected $casts = [
        'bank_balance_stable'    => 'boolean',
        'has_fixed_deposit'      => 'boolean',
        'has_investments'        => 'boolean',
        'has_employment_gaps'    => 'boolean',
        'spouse_employed'        => 'boolean',
        'children_staying_home'  => 'boolean',
        'has_real_estate'        => 'boolean',
        'has_car'                => 'boolean',
        'has_business'           => 'boolean',
        'has_schengen_visa'      => 'boolean',
        'has_us_visa'            => 'boolean',
        'has_uk_visa'            => 'boolean',
        'has_overstay'           => 'boolean',
        'has_criminal_record'    => 'boolean',
        'has_return_ticket'      => 'boolean',
        'has_hotel_booking'      => 'boolean',
        'has_invitation_letter'  => 'boolean',
        'sponsor_covers_expenses'=> 'boolean',
        'years_at_current_job'   => 'decimal:1',
        'total_work_experience'  => 'decimal:1',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Client\Models\Client::class);
    }
}
