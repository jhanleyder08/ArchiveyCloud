<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrdImportLog extends Model
{
    protected $fillable = [
        'trd_table_id',
        'import_configuration_id',
        'filename',
        'import_type',
        'total_records',
        'imported_records',
        'failed_records',
        'errors',
        'status',
        'imported_by'
    ];

    protected $casts = [
        'errors' => 'array',
        'total_records' => 'integer',
        'imported_records' => 'integer',
        'failed_records' => 'integer'
    ];

    public function trdTable(): BelongsTo
    {
        return $this->belongsTo(TrdTable::class);
    }

    public function importConfiguration(): BelongsTo
    {
        return $this->belongsTo(TrdImportConfiguration::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    // Calcular porcentaje de éxito
    public function getSuccessRateAttribute()
    {
        if ($this->total_records == 0) {
            return 0;
        }

        return round(($this->imported_records / $this->total_records) * 100, 2);
    }

    // Marcar como completado
    public function markAsCompleted()
    {
        $this->update([
            'status' => 'completed'
        ]);
    }

    // Marcar como fallido
    public function markAsFailed($errors = [])
    {
        $this->update([
            'status' => 'failed',
            'errors' => $errors
        ]);
    }
}
