<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrdTable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'code',
        'entity_name',
        'entity_code',
        'version',
        'status',
        'approval_date',
        'effective_date',
        'expiry_date',
        'created_by',
        'approved_by',
        'metadata'
    ];

    protected $casts = [
        'metadata' => 'array',
        'approval_date' => 'date',
        'effective_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TrdSection::class)->orderBy('order_index');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(TrdVersion::class)->orderBy('created_at', 'desc');
    }

    public function importLogs(): HasMany
    {
        return $this->hasMany(TrdImportLog::class);
    }

    // Crear nueva versión
    public function createVersion($changesSummary, $changeNotes = null, $userId = null)
    {
        $snapshot = [
            'trd_data' => $this->toArray(),
            'sections' => $this->sections()->with(['series.subseries'])->get()->toArray()
        ];

        return $this->versions()->create([
            'version' => $this->getNextVersion(),
            'changes_summary' => $changesSummary,
            'full_snapshot' => $snapshot,
            'created_by' => $userId ?? auth()->id(),
            'change_notes' => $changeNotes
        ]);
    }

    private function getNextVersion()
    {
        $lastVersion = $this->versions()->first();
        if (!$lastVersion) {
            return '1.0';
        }

        $versionParts = explode('.', $lastVersion->version);
        $versionParts[1] = (int)$versionParts[1] + 1;
        
        return implode('.', $versionParts);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByEntity($query, $entityCode)
    {
        return $query->where('entity_code', $entityCode);
    }
}
