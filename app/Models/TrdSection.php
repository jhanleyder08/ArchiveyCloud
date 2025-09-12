<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrdSection extends Model
{
    protected $fillable = [
        'trd_table_id',
        'section_code',
        'section_name',
        'description',
        'order_index'
    ];

    public function trdTable(): BelongsTo
    {
        return $this->belongsTo(TrdTable::class);
    }

    public function series(): HasMany
    {
        return $this->hasMany(TrdSeries::class)->orderBy('order_index');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }
}
