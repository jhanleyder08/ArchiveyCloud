<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ConfiguracionServicio extends Model
{
    use HasFactory;

    protected $table = 'configuraciones_servicios';

    const CLAVE_SERVICIOS_EXTERNOS = 'servicios_externos';

    protected $fillable = [
        'clave',
        'email_habilitado',
        'sms_habilitado',
        'resumen_diario_hora',
        'throttling_email',
        'throttling_sms',
        'destinatarios_resumen',
        'ambiente',
        'mail_driver',
        'queue_connection',
        'metadata',
        'activa'
    ];

    protected $casts = [
        'email_habilitado' => 'boolean',
        'sms_habilitado' => 'boolean',
        'resumen_diario_hora' => 'datetime:H:i',
        'throttling_email' => 'integer',
        'throttling_sms' => 'integer',
        'destinatarios_resumen' => 'array',
        'metadata' => 'array',
        'activa' => 'boolean',
    ];

    /**
     * Obtener la configuración de servicios externos
     */
    public static function obtenerConfiguracionServiciosExternos(): array
    {
        $config = static::where('clave', static::CLAVE_SERVICIOS_EXTERNOS)
            ->where('activa', true)
            ->first();

        $defaults = [
            'email_habilitado' => true,
            'sms_habilitado' => config('app.env') !== 'production',
            'resumen_diario_hora' => '08:00',
            'throttling_email' => 5,
            'throttling_sms' => 3,
            'destinatarios_resumen' => [],
        ];

        if (!$config) {
            return array_merge($defaults, [
                'ambiente' => config('app.env'),
                'mail_driver' => config('mail.default'),
                'queue_connection' => config('queue.default'),
            ]);
        }

        return array_merge($defaults, [
            'email_habilitado' => $config->email_habilitado,
            'sms_habilitado' => $config->sms_habilitado,
            'resumen_diario_hora' => $config->resumen_diario_hora->format('H:i'),
            'throttling_email' => $config->throttling_email,
            'throttling_sms' => $config->throttling_sms,
            'destinatarios_resumen' => $config->destinatarios_resumen ?? [],
            'ambiente' => config('app.env'),
            'mail_driver' => config('mail.default'),
            'queue_connection' => config('queue.default'),
        ]);
    }

    /**
     * Actualizar o crear configuración de servicios externos
     */
    public static function actualizarConfiguracionServiciosExternos(array $data): array
    {
        $config = static::updateOrCreate(
            [
                'clave' => static::CLAVE_SERVICIOS_EXTERNOS,
                'activa' => true
            ],
            array_merge($data, [
                'ambiente' => config('app.env'),
                'mail_driver' => config('mail.default'),
                'queue_connection' => config('queue.default'),
                'metadata' => [
                    'updated_at' => now()->toISOString(),
                    'updated_by' => auth()->id(),
                ]
            ])
        );

        return $config->fresh()->only([
            'email_habilitado',
            'sms_habilitado', 
            'resumen_diario_hora',
            'throttling_email',
            'throttling_sms',
            'destinatarios_resumen',
            'ambiente',
            'mail_driver',
            'queue_connection'
        ]);
    }

    /**
     * Relación con usuarios destinatarios de resúmenes
     */
    public function destinatarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'configuracion_destinatarios', 'configuracion_id', 'user_id');
    }

    /**
     * Scopes
     */
    public function scopeActiva($query)
    {
        return $query->where('activa', true);
    }

    public function scopePorClave($query, $clave)
    {
        return $query->where('clave', $clave);
    }
}
