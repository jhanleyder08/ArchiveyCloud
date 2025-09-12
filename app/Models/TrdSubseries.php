<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrdSubseries extends Model
{
    protected $fillable = [
        'trd_series_id',
        'subseries_code',
        'subseries_name',
        'description',
        'document_type',
        'retention_archive_management',
        'retention_central_archive',
        'final_disposition',
        'access_restrictions',
        'procedure',
        'order_index'
    ];

    protected $casts = [
        'retention_archive_management' => 'integer',
        'retention_central_archive' => 'integer',
    ];

    public function series(): BelongsTo
    {
        return $this->belongsTo(TrdSeries::class, 'trd_series_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_index');
    }

    public function scopeByDisposition($query, $disposition)
    {
        return $query->where('final_disposition', $disposition);
    }

    // Calcular tiempo total de retención
    public function getTotalRetentionAttribute()
    {
        return $this->retention_archive_management + $this->retention_central_archive;
    }

    // Verificar si está en conservación total
    public function isConservationTotal()
    {
        return $this->final_disposition === 'conservation_total';
    }
}
