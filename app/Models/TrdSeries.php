<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrdSeries extends Model
{
    protected $fillable = [
        'trd_section_id',
        'series_code',
        'series_name',
        'description',
        'order_index'
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(TrdSection::class, 'trd_section_id');
    }

    public function subseries(): HasMany
    {
        return $this->hasMany(TrdSubseries::class)->orderBy('order_index');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }
}
