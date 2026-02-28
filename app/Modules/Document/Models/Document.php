<?php

namespace App\Modules\Document\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends BaseModel
{
    use HasTenant;

    protected $fillable = [
        'agency_id',
        'case_id',
        'client_id',
        'uploaded_by',
        'type',
        'original_name',
        'file_path',
        'mime_type',
        'size',
        'status',
        'version',
        'notes',
        'ocr_status',
        'extracted_data',
        'ocr_processed_at',
    ];

    protected $casts = [
        'extracted_data'   => 'array',
        'ocr_processed_at' => 'datetime',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Case\Models\VisaCase::class, 'case_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\Client\Models\Client::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(\App\Modules\User\Models\User::class, 'uploaded_by');
    }
}
