<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CCDImportacion extends Model
{
    use HasFactory;

    protected $table = 'ccd_importaciones';

    protected $fillable = [
        'ccd_id',
        'tipo',
        'formato',
        'nombre_archivo',
        'ruta_archivo',
        'estado',
        'registros_procesados',
        'registros_error',
        'errores',
        'estadisticas',
        'usuario_id',
    ];

    protected $casts = [
        'errores' => 'array',
        'estadisticas' => 'array',
    ];

    // Tipos
    const TIPO_IMPORTACION = 'importacion';
    const TIPO_EXPORTACION = 'exportacion';

    // Formatos
    const FORMATO_XML = 'xml';
    const FORMATO_EXCEL = 'excel';
    const FORMATO_CSV = 'csv';
    const FORMATO_JSON = 'json';

    // Estados
    const ESTADO_PROCESANDO = 'procesando';
    const ESTADO_COMPLETADO = 'completado';
    const ESTADO_ERROR = 'error';

    /**
     * CCD asociado
     */
    public function ccd(): BelongsTo
    {
        return $this->belongsTo(CCD::class, 'ccd_id');
    }

    /**
     * Usuario que realizó la operación
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Marcar como completado
     */
    public function marcarCompletado(): void
    {
        $this->estado = self::ESTADO_COMPLETADO;
        $this->save();
    }

    /**
     * Marcar como error
     */
    public function marcarError(array $errores): void
    {
        $this->estado = self::ESTADO_ERROR;
        $this->errores = $errores;
        $this->save();
    }

    /**
     * Incrementar contador de registros procesados
     */
    public function incrementarProcesados(int $cantidad = 1): void
    {
        $this->registros_procesados += $cantidad;
        $this->save();
    }

    /**
     * Incrementar contador de errores
     */
    public function incrementarErrores(int $cantidad = 1): void
    {
        $this->registros_error += $cantidad;
        $this->save();
    }
}
