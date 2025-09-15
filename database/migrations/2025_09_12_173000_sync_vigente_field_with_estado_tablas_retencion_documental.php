<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Sincronizar el campo vigente con el estado actual de las TRDs existentes
     */
    public function up(): void
    {
        // Actualizar todos los registros donde estado = 'vigente' pero vigente = false
        $updated = DB::table('tablas_retencion_documental')
            ->where('estado', 'vigente')
            ->where('vigente', false)
            ->whereNull('deleted_at')
            ->update(['vigente' => true]);

        echo "TRDs actualizadas (estado vigente → vigente = true): {$updated}\n";

        // Actualizar todos los registros donde estado != 'vigente' pero vigente = true
        $updated2 = DB::table('tablas_retencion_documental')
            ->where('estado', '!=', 'vigente')
            ->where('vigente', true)
            ->whereNull('deleted_at')
            ->update(['vigente' => false]);

        echo "TRDs actualizadas (estado no vigente → vigente = false): {$updated2}\n";

        // Mostrar resumen final
        $vigentesCorrect = DB::table('tablas_retencion_documental')
            ->where('estado', 'vigente')
            ->where('vigente', true)
            ->whereNull('deleted_at')
            ->count();

        $noVigentesCorrect = DB::table('tablas_retencion_documental')
            ->where('estado', '!=', 'vigente')
            ->where('vigente', false)
            ->whereNull('deleted_at')
            ->count();

        echo "Resumen final:\n";
        echo "- TRDs vigentes (estado y campo sincronizados): {$vigentesCorrect}\n";
        echo "- TRDs no vigentes (estado y campo sincronizados): {$noVigentesCorrect}\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Esta migración es de corrección de datos, no tiene rollback
        echo "Esta migración corrige datos inconsistentes y no tiene rollback.\n";
    }
};
