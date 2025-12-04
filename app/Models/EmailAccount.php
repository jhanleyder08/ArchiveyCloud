<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailAccount extends Model
{
    protected $fillable = [
        'nombre',
        'email',
        'password',
        'host',
        'port',
        'encryption',
        'protocol',
        'auto_capture',
        'folders',
        'filters',
        'serie_documental_id',
        'active',
        'last_capture_at',
        'total_captured',
    ];

    protected $casts = [
        'folders' => 'array',
        'filters' => 'array',
        'auto_capture' => 'boolean',
        'active' => 'boolean',
        'last_capture_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    public function captures(): HasMany
    {
        return $this->hasMany(EmailCapture::class);
    }

    public function serieDocumental(): BelongsTo
    {
        return $this->belongsTo(SerieDocumental::class);
    }

    public function getDecryptedPassword(): string
    {
        return decrypt($this->password);
    }

    public function setPasswordAttribute($value): void
    {
        $this->attributes['password'] = encrypt($value);
    }
}
