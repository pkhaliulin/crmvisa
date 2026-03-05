<?php

namespace App\Modules\Payment\Models;

use App\Modules\Agency\Models\Agency;
use App\Modules\Case\Models\VisaCase;
use App\Modules\Group\Models\CaseGroup;
use App\Modules\PublicPortal\Models\PublicUser;
use App\Modules\Service\Models\AgencyServicePackage;
use App\Support\Abstracts\BaseModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientPayment extends BaseModel
{
    protected $table = 'client_payments';

    protected $fillable = [
        'case_id',
        'group_id',
        'public_user_id',
        'agency_id',
        'package_id',
        'amount',
        'currency',
        'provider',
        'provider_transaction_id',
        'status',
        'paid_at',
        'metadata',
        'expires_at',
    ];

    protected $casts = [
        'amount'     => 'integer',
        'paid_at'    => 'datetime',
        'expires_at' => 'datetime',
        'metadata'   => 'array',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class, 'case_id');
    }

    public function publicUser(): BelongsTo
    {
        return $this->belongsTo(PublicUser::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(AgencyServicePackage::class, 'package_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CaseGroup::class, 'group_id');
    }
}
