<?php

namespace App\Services;

use App\Models\Expediente;
use App\Models\ExpedienteTransferencia;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ExpedienteService
{
    /**
     * Crear nuevo expediente
     */
    public function crear(array $datos, User $usuario): Expediente
    {
        DB::beginTransaction();
        
        try {
            $expediente = new Expediente($datos);
            $expediente->responsable_id = $datos['responsable_id'] ?? $usuario->id;
            $expediente->created_by = $usuario->id;
            $expediente->fecha_apertura = $datos['fecha_apertura'] ?? now();
            $expediente->estado = 'en_tramite';
            $expediente->version = '1.0';
            
            // Generar código automático si no se proporciona
            if (empty($datos['codigo'])) {
                $expediente->codigo = $this->generarCodigo($expediente);
            }
            
            $expediente->save();

            // Registrar en historial
            $this->registrarCambioEstado($expediente, null, 'en_tramite', 'Expediente creado', $usuario);

            Log::info('Expediente creado', ['expediente_id' => $expediente->id, 'usuario' => $usuario->id]);

            DB::commit();
            return $expediente;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al crear expediente', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Actualizar expediente
     */
    public function actualizar(Expediente $expediente, array $datos, User $usuario): Expediente
    {
        if ($expediente->cerrado) {
            throw new Exception('No se puede modificar un expediente cerrado');
        }

        if ($expediente->bloqueado) {
            throw new Exception('El expediente está bloqueado');
        }

        DB::beginTransaction();
        
        try {
            $expediente->fill($datos);
            $expediente->updated_by = $usuario->id;
            $expediente->save();

            Log::info('Expediente actualizado', ['expediente_id' => $expediente->id]);

            DB::commit();
            return $expediente;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar expediente', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Cambiar estado del expediente
     */
    public function cambiarEstado(Expediente $expediente, string $nuevoEstado, string $observaciones, User $usuario): Expediente
    {
        DB::beginTransaction();
        
        try {
            $estadoAnterior = $expediente->estado;
            $expediente->estado = $nuevoEstado;
            
            // Actualizar fechas según el estado
            switch ($nuevoEstado) {
                case 'activo':
                    if (!$expediente->fecha_apertura) {
                        $expediente->fecha_apertura = now();
                    }
                    break;
                case 'semiactivo':
                    $expediente->fecha_transferencia_archivo_gestion = now();
                    break;
                case 'inactivo':
                    $expediente->fecha_transferencia_archivo_central = now();
                    break;
                case 'historico':
                    $expediente->fecha_transferencia_archivo_historico = now();
                    break;
            }
            
            $expediente->save();

            // Registrar cambio en historial
            $this->registrarCambioEstado($expediente, $estadoAnterior, $nuevoEstado, $observaciones, $usuario);

            Log::info('Estado de expediente cambiado', [
                'expediente_id' => $expediente->id,
                'estado_anterior' => $estadoAnterior,
                'estado_nuevo' => $nuevoEstado
            ]);

            DB::commit();
            return $expediente;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al cambiar estado', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Cerrar expediente
     */
    public function cerrar(Expediente $expediente, User $usuario): Expediente
    {
        if ($expediente->cerrado) {
            throw new Exception('El expediente ya está cerrado');
        }

        DB::beginTransaction();
        
        try {
            $expediente->cerrado = true;
            $expediente->fecha_cierre = now();
            $expediente->save();

            // Calcular y actualizar fechas de disposición según TRD
            $this->calcularDisposicionFinal($expediente);

            $this->registrarCambioEstado($expediente, $expediente->estado, $expediente->estado, 'Expediente cerrado', $usuario);

            Log::info('Expediente cerrado', ['expediente_id' => $expediente->id]);

            DB::commit();
            return $expediente;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Agregar documento al expediente
     */
    public function agregarDocumento(Expediente $expediente, int $documentoId, array $datos, User $usuario): void
    {
        if ($expediente->cerrado) {
            throw new Exception('No se pueden agregar documentos a un expediente cerrado');
        }

        DB::beginTransaction();
        
        try {
            $expediente->documentos()->attach($documentoId, [
                'orden' => $datos['orden'] ?? $expediente->documentos()->count() + 1,
                'fecha_incorporacion' => now(),
                'motivo_incorporacion' => $datos['motivo'] ?? null,
                'documento_principal' => $datos['es_principal'] ?? false,
                'incorporado_por' => $usuario->id,
            ]);

            // Actualizar contador de documentos
            $expediente->numero_documentos = $expediente->documentos()->count();
            $expediente->save();

            Log::info('Documento agregado al expediente', [
                'expediente_id' => $expediente->id,
                'documento_id' => $documentoId
            ]);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Crear transferencia de expediente
     */
    public function crearTransferencia(Expediente $expediente, array $datos, User $usuario): ExpedienteTransferencia
    {
        DB::beginTransaction();
        
        try {
            $transferencia = new ExpedienteTransferencia($datos);
            $transferencia->expediente_id = $expediente->id;
            $transferencia->fecha_solicitud = now();
            $transferencia->solicitado_por = $usuario->id;
            $transferencia->estado = 'pendiente';
            $transferencia->save();

            // Cambiar estado del expediente
            $this->cambiarEstado($expediente, 'en_transferencia', 'Transferencia solicitada', $usuario);

            Log::info('Transferencia creada', [
                'expediente_id' => $expediente->id,
                'transferencia_id' => $transferencia->id
            ]);

            DB::commit();
            return $transferencia;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Verificar integridad del expediente
     */
    public function verificarIntegridad(Expediente $expediente): array
    {
        $errores = [];
        $advertencias = [];

        // Verificar que tiene documentos
        if ($expediente->numero_documentos === 0) {
            $advertencias[] = 'El expediente no tiene documentos asociados';
        }

        // Verificar clasificación
        if (!$expediente->serie_id) {
            $errores[] = 'El expediente no tiene serie documental asignada';
        }

        // Verificar fechas
        if ($expediente->cerrado && !$expediente->fecha_cierre) {
            $errores[] = 'El expediente está marcado como cerrado pero no tiene fecha de cierre';
        }

        // Verificar hash de integridad
        if ($expediente->hash_integridad) {
            $hashActual = $this->calcularHashIntegridad($expediente);
            if ($hashActual !== $expediente->hash_integridad) {
                $errores[] = 'El hash de integridad no coincide. Posible modificación no autorizada';
            }
        }

        return [
            'valido' => empty($errores),
            'errores' => $errores,
            'advertencias' => $advertencias,
        ];
    }

    /**
     * Generar código de expediente
     */
    private function generarCodigo(Expediente $expediente): string
    {
        $serie = $expediente->serie_id ? $expediente->serie->codigo : 'EXP';
        $año = date('Y');
        $consecutivo = Expediente::whereYear('created_at', $año)->count() + 1;
        
        return sprintf('%s-%s-%04d', $serie, $año, $consecutivo);
    }

    /**
     * Registrar cambio de estado en historial
     */
    private function registrarCambioEstado(Expediente $expediente, ?string $estadoAnterior, string $estadoNuevo, string $observaciones, User $usuario): void
    {
        DB::table('expediente_historial')->insert([
            'expediente_id' => $expediente->id,
            'estado_anterior' => $estadoAnterior ?? 'ninguno',
            'estado_nuevo' => $estadoNuevo,
            'observaciones' => $observaciones,
            'realizado_por' => $usuario->id,
            'fecha_cambio' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Calcular disposición final según TRD
     */
    private function calcularDisposicionFinal(Expediente $expediente): void
    {
        if (!$expediente->serie_id) {
            return;
        }

        $serie = $expediente->serie;
        if (!$serie || !$serie->retencion) {
            return;
        }

        $retencion = $serie->retencion;
        
        $expediente->anos_archivo_gestion = $retencion->anos_archivo_gestion;
        $expediente->anos_archivo_central = $retencion->anos_archivo_central;
        $expediente->disposicion_final = $retencion->disposicion_final;
        
        $expediente->save();
    }

    /**
     * Calcular hash de integridad
     */
    private function calcularHashIntegridad(Expediente $expediente): string
    {
        $datos = [
            'codigo' => $expediente->codigo,
            'documentos' => $expediente->documentos()->pluck('id')->sort()->toArray(),
            'fecha_apertura' => $expediente->fecha_apertura,
            'fecha_cierre' => $expediente->fecha_cierre,
        ];
        
        return hash('sha512', json_encode($datos));
    }

    /**
     * Obtener estadísticas del expediente
     */
    public function getEstadisticas(Expediente $expediente): array
    {
        return [
            'numero_documentos' => $expediente->numero_documentos,
            'tamano_total' => $expediente->tamano_total_bytes,
            'dias_abierto' => $expediente->fecha_apertura 
                ? Carbon::parse($expediente->fecha_apertura)->diffInDays(now()) 
                : 0,
            'numero_accesos' => DB::table('expediente_accesos')
                ->where('expediente_id', $expediente->id)
                ->count(),
            'ultimo_acceso' => DB::table('expediente_accesos')
                ->where('expediente_id', $expediente->id)
                ->latest('fecha_acceso')
                ->value('fecha_acceso'),
        ];
    }
}
