<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAttachment extends Model
{
    protected $fillable = [
        'email_capture_id',
        'filename',
        'mime_type',
        'size',
        'path',
        'documento_id',
    ];

    public function emailCapture(): BelongsTo
    {
        return $this->belongsTo(EmailCapture::class);
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function getSizeInMB(): float
    {
        return round($this->size / 1024 / 1024, 2);
    }
}
