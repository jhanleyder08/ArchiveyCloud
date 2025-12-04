<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuthentication extends Model
{
    protected $fillable = [
        'user_id',
        'enabled',
        'secret',
        'recovery_codes',
        'confirmed_at',
        'method',
        'phone_number',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'recovery_codes' => 'array',
        'confirmed_at' => 'datetime',
    ];

    protected $hidden = [
        'secret',
        'recovery_codes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isConfirmed(): bool
    {
        return !is_null($this->confirmed_at);
    }

    public function confirm(): void
    {
        $this->confirmed_at = now();
        $this->save();
    }
}
