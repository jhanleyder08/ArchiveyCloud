<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrdVersion extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'trd_table_id',
        'version',
        'changes_summary',
        'full_snapshot',
        'created_by',
        'change_notes',
        'created_at'
    ];

    protected $casts = [
        'changes_summary' => 'array',
        'full_snapshot' => 'array',
        'created_at' => 'datetime'
    ];

    public function trdTable(): BelongsTo
    {
        return $this->belongsTo(TrdTable::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Restaurar versión
    public function restore()
    {
        $snapshot = $this->full_snapshot;
        $trdTable = $this->trdTable;

        // Actualizar datos principales de TRD
        $trdTable->update($snapshot['trd_data']);

        // Recrear estructura completa
        $trdTable->sections()->delete();
        
        foreach ($snapshot['sections'] as $sectionData) {
            $section = $trdTable->sections()->create([
                'section_code' => $sectionData['section_code'],
                'section_name' => $sectionData['section_name'],
                'description' => $sectionData['description'],
                'order_index' => $sectionData['order_index']
            ]);

            foreach ($sectionData['series'] as $seriesData) {
                $series = $section->series()->create([
                    'series_code' => $seriesData['series_code'],
                    'series_name' => $seriesData['series_name'],
                    'description' => $seriesData['description'],
                    'order_index' => $seriesData['order_index']
                ]);

                foreach ($seriesData['subseries'] as $subseriesData) {
                    $series->subseries()->create($subseriesData);
                }
            }
        }

        return $trdTable;
    }
}
