<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCapture extends Model
{
    protected $fillable = [
        'email_account_id',
        'message_id',
        'subject',
        'from',
        'to',
        'cc',
        'body_text',
        'body_html',
        'attachments_count',
        'email_date',
        'status',
        'documento_id',
        'error_message',
    ];

    protected $casts = [
        'email_date' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(EmailAccount::class, 'email_account_id');
    }

    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(EmailAttachment::class);
    }
}
