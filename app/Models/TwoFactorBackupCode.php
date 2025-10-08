<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorBackupCode extends Model
{
    protected $fillable = [
        'user_id',
        'code',
        'used',
        'used_at',
    ];

    protected $casts = [
        'used' => 'boolean',
        'used_at' => 'datetime',
    ];

    protected $hidden = [
        'code',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Marcar el código como usado
     */
    public function markAsUsed(): void
    {
        $this->used = true;
        $this->used_at = now();
        $this->save();
    }

    /**
     * Verificar si el código está disponible
     */
    public function isAvailable(): bool
    {
        return !$this->used;
    }

    /**
     * Scope para códigos disponibles
     */
    public function scopeAvailable($query)
    {
        return $query->where('used', false);
    }

    /**
     * Scope para códigos usados
     */
    public function scopeUsed($query)
    {
        return $query->where('used', true);
    }
}
