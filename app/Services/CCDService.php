<?php

namespace App\Services;

use App\Models\CCD;
use App\Models\CCDNivel;
use App\Models\CCDVocabulario;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class CCDService
{
    public function crear(array $datos, User $usuario): CCD
    {
        DB::beginTransaction();
        try {
            $ccd = new CCD($datos);
            $ccd->created_by = $usuario->id;
            $ccd->version = $datos['version'] ?? '1.0';
            $ccd->estado = 'borrador';
            $ccd->save();
            DB::commit();
            return $ccd;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function agregarNivel(CCD $ccd, array $datos): CCDNivel
    {
        DB::beginTransaction();
        try {
            $nivel = new CCDNivel($datos);
            $nivel->ccd_id = $ccd->id;
            if (!isset($datos['nivel'])) {
                $nivel->nivel = $nivel->parent_id ? CCDNivel::find($nivel->parent_id)->nivel + 1 : 1;
            }
            $nivel->save();
            $nivel->actualizarRuta();
            DB::commit();
            return $nivel;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function actualizar(CCD $ccd, array $datos, User $usuario): CCD
    {
        DB::beginTransaction();
        try {
            // Actualizar los campos del CCD
            $ccd->fill($datos);
            $ccd->updated_by = $usuario->id;
            $ccd->save();
            
            DB::commit();
            return $ccd;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar CCD', [
                'ccd_id' => $ccd->id,
                'error' => $e->getMessage(),
                'datos' => $datos
            ]);
            throw $e;
        }
    }

    public function obtenerEstructuraJerarquica(CCD $ccd): array
    {
        return $ccd->getEstructuraJerarquica();
    }
}
