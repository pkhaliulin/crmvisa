<?php

namespace App\Modules\Payment\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LedgerEntry extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';
    public $timestamps   = false;

    protected $fillable = [
        'id', 'transaction_id', 'agency_id', 'account',
        'debit', 'credit', 'currency', 'description', 'metadata', 'created_at',
    ];

    protected $casts = [
        'debit'      => 'integer',
        'credit'     => 'integer',
        'metadata'   => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $entry) {
            if (! $entry->id) {
                $entry->id = Str::uuid()->toString();
            }
            if (! $entry->created_at) {
                $entry->created_at = now();
            }
        });
    }

    /**
     * Записать пару дебет/кредит (двойная запись).
     */
    public static function record(
        string  $debitAccount,
        string  $creditAccount,
        int     $amount,
        ?string $transactionId = null,
        ?string $agencyId = null,
        string  $description = '',
        array   $metadata = [],
    ): array {
        $debitEntry = self::create([
            'transaction_id' => $transactionId,
            'agency_id'      => $agencyId,
            'account'        => $debitAccount,
            'debit'          => $amount,
            'credit'         => 0,
            'description'    => $description,
            'metadata'       => $metadata,
        ]);

        $creditEntry = self::create([
            'transaction_id' => $transactionId,
            'agency_id'      => $agencyId,
            'account'        => $creditAccount,
            'debit'          => 0,
            'credit'         => $amount,
            'description'    => $description,
            'metadata'       => $metadata,
        ]);

        return [$debitEntry, $creditEntry];
    }
}
