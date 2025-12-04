<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    /**
     * Información general de la API
     */
    public function info(Request $request): JsonResponse
    {
        $token = $request->attributes->get('api_token');
        
        return response()->json([
            'success' => true,
            'data' => [
                'sistema' => 'ArchiveyCloud',
                'version' => '1.0.0',
                'timestamp' => now()->toISOString(),
                'token_info' => [
                    'nombre' => $token->nombre,
                    'descripcion' => $token->descripcion,
                    'permisos' => $token->permisos,
                    'expira' => $token->fecha_expiracion?->toISOString(),
                    'usos_realizados' => $token->usos_realizados,
                    'limite_usos' => $token->limite_usos,
                    'usos_restantes' => $token->limite_usos ? ($token->limite_usos - $token->usos_realizados) : null,
                    'restricciones_ip' => $token->restricciones_ip,
                    'ultimo_uso' => $token->ultimo_uso_at?->toISOString()
                ]
            ]
        ]);
    }
}
