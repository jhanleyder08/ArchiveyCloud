<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrdTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'template_structure',
        'is_active'
    ];

    protected $casts = [
        'template_structure' => 'array',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Crear TRD desde plantilla
    public function createTrdFromTemplate($data)
    {
        $trdData = array_merge([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'code' => $data['code'],
            'entity_name' => $data['entity_name'],
            'entity_code' => $data['entity_code'],
            'created_by' => auth()->id()
        ], $this->template_structure);

        return TrdTable::create($trdData);
    }
}
