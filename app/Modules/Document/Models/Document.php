<?php

namespace App\Modules\Document\Models;

use App\Support\Abstracts\BaseModel;
use App\Support\Traits\HasTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

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
        'extracted_data'   => 'encrypted:array',
        'ocr_processed_at' => 'datetime',
    ];

    protected $appends = ['url', 'preview_url', 'thumbnail_url'];

    public function getUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;
        return URL::temporarySignedRoute(
            'documents.download',
            now()->addMinutes(30),
            ['document' => $this->id]
        );
    }

    public function getPreviewUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;
        return URL::temporarySignedRoute(
            'documents.preview',
            now()->addMinutes(30),
            ['document' => $this->id]
        );
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (!$this->file_path) return null;
        return URL::temporarySignedRoute(
            'documents.thumbnail',
            now()->addMinutes(60),
            ['document' => $this->id]
        );
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
