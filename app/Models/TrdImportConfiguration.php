<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrdImportConfiguration extends Model
{
    protected $fillable = [
        'name',
        'import_type',
        'field_mapping',
        'validation_rules',
        'is_active'
    ];

    protected $casts = [
        'field_mapping' => 'array',
        'validation_rules' => 'array',
        'is_active' => 'boolean'
    ];

    public function importLogs(): HasMany
    {
        return $this->hasMany(TrdImportLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('import_type', $type);
    }

    // Validar estructura de archivo según configuración
    public function validateFileStructure($data)
    {
        $errors = [];
        $mapping = $this->field_mapping;
        $rules = $this->validation_rules;

        foreach ($data as $rowIndex => $row) {
            foreach ($mapping as $fieldName => $columnIndex) {
                if (!isset($row[$columnIndex])) {
                    $errors[] = "Fila {$rowIndex}: Campo '{$fieldName}' no encontrado";
                    continue;
                }

                $value = $row[$columnIndex];
                
                if (isset($rules[$fieldName])) {
                    foreach ($rules[$fieldName] as $rule) {
                        if (!$this->validateRule($value, $rule)) {
                            $errors[] = "Fila {$rowIndex}: Campo '{$fieldName}' no cumple regla '{$rule}'";
                        }
                    }
                }
            }
        }

        return $errors;
    }

    private function validateRule($value, $rule)
    {
        switch ($rule) {
            case 'required':
                return !empty($value);
            case 'numeric':
                return is_numeric($value);
            case 'date':
                return strtotime($value) !== false;
            default:
                return true;
        }
    }
}
