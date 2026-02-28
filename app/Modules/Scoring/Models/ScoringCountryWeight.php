<?php

namespace App\Modules\Scoring\Models;

use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ScoringCountryWeight extends Model
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['country_code', 'block_code', 'weight'];
}
