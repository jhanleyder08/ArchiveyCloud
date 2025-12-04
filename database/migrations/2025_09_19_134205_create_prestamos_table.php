<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prestamos', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_prestamo', ['expediente', 'documento']);
            $table->foreignId('expediente_id')->nullable()->constrained('expedientes')->onDelete('cascade');
            $table->foreignId('documento_id')->nullable()->constrained('documentos')->onDelete('cascade');
            $table->foreignId('solicitante_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('prestamista_id')->constrained('users')->onDelete('cascade');
            $table->text('motivo');
            $table->datetime('fecha_prestamo');
            $table->datetime('fecha_devolucion_esperada');
            $table->datetime('fecha_devolucion_real')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('observaciones_devolucion')->nullable();
            $table->enum('estado', ['prestado', 'devuelto', 'cancelado'])->default('prestado');
            $table->enum('estado_devolucion', ['bueno', 'dañado', 'perdido'])->nullable();
            $table->integer('renovaciones')->default(0);
            $table->timestamps();
            
            // Índices
            $table->index(['estado', 'fecha_devolucion_esperada']);
            $table->index(['tipo_prestamo', 'estado']);
            $table->index('solicitante_id');
            $table->index('prestamista_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prestamos');
    }
};
