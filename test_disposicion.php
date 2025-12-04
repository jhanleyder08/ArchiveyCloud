<?php

use App\Models\DisposicionFinal;
use App\Models\PistaAuditoria;
use App\Models\Expediente;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

try {
    echo "Starting test...\n";

    $user = User::first();
    if (!$user) {
        echo "No user found.\n";
        exit;
    }
    auth()->login($user);
    echo "Logged in as {$user->name}\n";

    $expediente = Expediente::first();
    if (!$expediente) {
        echo "No expediente found.\n";
        // Create a dummy expediente if needed, or just skip
        // exit;
    }
    $expedienteId = $expediente ? $expediente->id : null;
    echo "Expediente ID: " . ($expedienteId ?? 'None') . "\n";

    $validated = [
        'tipo_item' => 'expediente',
        'expediente_id' => $expedienteId,
        'documento_id' => null,
        'tipo_disposicion' => 'conservacion_permanente',
        'fecha_propuesta' => Carbon::now(),
        'justificacion' => 'Test justification from tinker',
        'observaciones' => 'Test observations',
        'responsable_id' => $user->id,
        'responsable_externo_nombre' => null,
    ];

    echo "Data prepared. Starting transaction...\n";

    DB::transaction(function () use ($validated, $user) {
        echo "Inside transaction...\n";
        
        $datosResponsableExterno = null;

        echo "Creating DisposicionFinal...\n";
        $disposicion = DisposicionFinal::create([
            'expediente_id' => $validated['expediente_id'],
            'documento_id' => $validated['documento_id'],
            'responsable_id' => $validated['responsable_id'],
            'tipo_disposicion' => $validated['tipo_disposicion'],
            'estado' => DisposicionFinal::ESTADO_PENDIENTE,
            'fecha_propuesta' => $validated['fecha_propuesta'],
            'justificacion' => $validated['justificacion'],
            'observaciones' => $validated['observaciones'],
            'datos_responsable_externo' => $datosResponsableExterno,
        ]);
        echo "DisposicionFinal created with ID: {$disposicion->id}\n";

        echo "Creating PistaAuditoria...\n";
        PistaAuditoria::create([
            'usuario_id' => $user->id,
            'evento' => 'crear_disposicion_final',
            'accion' => 'crear',
            'tabla_afectada' => 'disposicion_finals',
            'registro_id' => $disposicion->id,
            'descripcion' => "Nueva disposición final propuesta: {$disposicion->tipo_disposicion}",
            'valores_nuevos' => $disposicion->toJson(),
        ]);
        echo "PistaAuditoria created.\n";
    });

    echo "Transaction committed.\n";
    echo "Test completed successfully.\n";

} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
